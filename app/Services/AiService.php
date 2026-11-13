<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use App\Models\Product;
use App\Support\Locales;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fitur AI kyread berbasis Claude: kenali buku dari foto dan asisten belanja.
 *
 * Kunci API hanya ada di server (ANTHROPIC_API_KEY); web & Android memanggil endpoint kyread.
 */
class AiService
{
    public function __construct(private readonly ?Client $client = null) {}

    public static function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * Kenali sampul/halaman buku dari foto.
     *
     * @return array{title: ?string, author: ?string, keywords: list<string>, description: string}
     */
    public function identifyBook(UploadedFile $image): array
    {
        $data = $this->ask(
            system: 'Kamu membantu toko buku online kyread mengenali buku dari foto. '
                .'Baca judul dan penulis dari sampul, punggung buku, atau halaman yang terlihat. '
                .'Jika foto bukan buku, isi title dan author dengan null dan beri kata kunci topik/genre yang relevan dengan isi foto. '
                .'Tulis description maksimal satu kalimat dalam bahasa: '.$this->replyLanguage().'.',
            messages: [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image',
                        'source' => [
                            'type' => 'base64',
                            'mediaType' => $image->getMimeType(),
                            'data' => base64_encode($image->get()),
                        ],
                    ],
                    ['type' => 'text', 'text' => 'Buku apa ini? Berikan judul, penulis, dan kata kunci pencarian.'],
                ],
            ]],
            schema: [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => ['string', 'null'], 'description' => 'Judul buku, null jika tidak terbaca'],
                    'author' => ['type' => ['string', 'null'], 'description' => 'Nama penulis, null jika tidak terbaca'],
                    'keywords' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '1-5 kata kunci pencarian: judul, penulis, genre, atau topik'],
                    'description' => ['type' => 'string'],
                ],
                'required' => ['title', 'author', 'keywords', 'description'],
                'additionalProperties' => false,
            ],
            maxTokens: 2048,
        );

        return [
            'title' => $data['title'] ?? null,
            'author' => $data['author'] ?? null,
            'keywords' => array_values(array_filter(array_map('strval', $data['keywords'] ?? []))),
            'description' => (string) ($data['description'] ?? ''),
        ];
    }

    /**
     * Cari produk berdasarkan hasil pengenalan gambar: judul dulu, lalu penulis, lalu kata kunci.
     *
     * @param  array{title: ?string, author: ?string, keywords: list<string>}  $identified
     * @return Collection<int, Product>
     */
    public function matchProducts(array $identified): Collection
    {
        $terms = collect([$identified['title'], $identified['author'], ...$identified['keywords']])
            ->filter(fn ($t) => filled($t) && mb_strlen(trim($t)) >= 3)
            ->map(fn ($t) => trim($t))
            ->unique()
            ->take(6);

        $found = collect();
        foreach ($terms as $term) {
            $found = $found->merge(
                Product::active()->with('category', 'store')->withAvg('reviews', 'rating')
                    ->filter(['q' => $term, 'sort' => 'bestseller'])
                    ->take(10)->get()
            );
            if ($found->count() >= 12) {
                break;
            }
        }

        return $found->unique('id')->values()->take(12);
    }

    /**
     * Asisten belanja: jawab pertanyaan & rekomendasikan buku dari katalog.
     *
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, products: Collection<int, Product>}
     */
    public function chat(array $history): array
    {
        $catalog = $this->catalogContext();

        $data = $this->ask(
            system: [
                [
                    'type' => 'text',
                    'text' => <<<'PROMPT'
                    Kamu adalah "Kyra", asisten belanja toko buku online kyread. Jawab dengan ramah dan ringkas (maksimal ±120 kata), dalam bahasa yang disebut di bagian akhir.

                    Yang bisa kamu bantu:
                    - Merekomendasikan buku dari KATALOG di bawah sesuai selera, mood, umur, atau anggaran pembeli.
                    - Menjelaskan isi/genre buku yang ada di katalog, membandingkan pilihan, dan memberi info harga/stok.
                    - Menjawab pertanyaan cara belanja di kyread: checkout, pembayaran (VA BCA/BNI/BRI/Mandiri, kartu kredit, PayPal; batas bayar 24 jam), pengiriman (ongkir per kg sesuai kurir), pembatalan, ulasan.

                    Aturan:
                    - Hanya rekomendasikan buku yang ada di katalog, dan masukkan id-nya ke product_ids (urut dari yang paling cocok, maksimal 6). Jangan mengarang judul yang tidak ada di katalog; kalau tidak ada yang cocok, katakan terus terang dan sarankan kategori terdekat.
                    - Sebut harga dalam format "Rp 73.000". Jangan menjanjikan diskon, promo, atau kebijakan yang tidak disebut di atas.
                    - Jika pertanyaan di luar topik buku dan belanja di kyread, arahkan kembali dengan sopan.
                    PROMPT,
                ],
                // Katalog di blok terpisah yang di-cache agar percakapan berikutnya lebih cepat & hemat.
                ['type' => 'text', 'text' => "KATALOG (id | judul | penulis | kategori | harga | stok | rating):\n".$catalog, 'cacheControl' => ['type' => 'ephemeral']],
                // Setelah blok cache: ganti bahasa tidak membuat cache katalog terbuang.
                ['type' => 'text', 'text' => 'Bahasa antarmuka pembeli: '.$this->replyLanguage().'. Tulis reply dalam bahasa ini, kecuali pembeli jelas menulis dalam bahasa lain. Judul buku tetap seperti di katalog.'],
            ],
            messages: $history,
            schema: [
                'type' => 'object',
                'properties' => [
                    'reply' => ['type' => 'string', 'description' => 'Jawaban untuk pembeli'],
                    'product_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Id buku dari katalog yang direkomendasikan'],
                ],
                'required' => ['reply', 'product_ids'],
                'additionalProperties' => false,
            ],
            maxTokens: 4096,
        );

        $ids = collect($data['product_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->take(6);
        $products = Product::active()->with('category', 'store')->withAvg('reviews', 'rating')
            ->whereKey($ids)->get()
            ->sortBy(fn (Product $p) => $ids->search($p->id))
            ->values();

        return ['reply' => (string) ($data['reply'] ?? ''), 'products' => $products];
    }

    /** Nama bahasa antarmuka aktif, mis. "日本語 (ja)". */
    private function replyLanguage(): string
    {
        return Locales::current()['native'].' ('.app()->getLocale().')';
    }

    /** Ringkasan katalog aktif (maks. 300 judul terlaris), di-cache 10 menit. */
    private function catalogContext(): string
    {
        return Cache::remember('ai.catalog', now()->addMinutes(10), fn () => Product::active()
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('sold_count')
            ->take(300)
            ->get()
            ->map(fn (Product $p) => implode(' | ', [
                $p->id,
                $p->title,
                $p->author,
                $p->category->name,
                'Rp '.number_format($p->price, 0, ',', '.'),
                $p->stock > 0 ? "stok {$p->stock}" : 'habis',
                $p->reviews_avg_rating ? number_format($p->reviews_avg_rating, 1) : '-',
            ]))
            ->implode("\n"));
    }

    /**
     * Satu panggilan Messages API dengan structured output. Mengembalikan JSON yang sudah di-decode.
     *
     * @throws RuntimeException pesan siap tampil untuk pengguna
     */
    private function ask(string|array $system, array $messages, array $schema, int $maxTokens): array
    {
        if (! self::enabled()) {
            throw new RuntimeException(__('Fitur AI belum diaktifkan di server.'));
        }

        $client = $this->client ?? new Client(apiKey: config('services.anthropic.key'));

        try {
            $message = $client->beta->messages->create(
                model: config('services.anthropic.model'),
                maxTokens: $maxTokens,
                system: $system,
                messages: $messages,
                // Tugas ringan & interaktif: effort rendah agar respons cepat.
                outputConfig: ['effort' => 'low', 'format' => ['type' => 'json_schema', 'schema' => $schema]],
                // Bila model menolak karena kebijakan, server Anthropic mencoba ulang di model pengganti.
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (APIConnectionException $e) {
            Log::warning('Claude API connection failed', ['error' => $e->getMessage()]);
            throw new RuntimeException(__('Asisten AI sedang tidak dapat dihubungi. Coba lagi sebentar.'));
        } catch (APIStatusException $e) {
            Log::warning('Claude API error', ['status' => $e->getCode(), 'type' => $e->type?->value, 'error' => $e->getMessage()]);
            throw new RuntimeException(match ($e->type?->value) {
                'rate_limit_error', 'overloaded_error' => __('Asisten AI sedang sibuk. Coba lagi beberapa saat lagi.'),
                default => __('Asisten AI gagal memproses permintaan.'),
            });
        }

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException(__('Maaf, permintaan ini tidak bisa diproses oleh asisten.'));
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        throw new RuntimeException(__('Asisten AI tidak memberikan jawaban. Coba ulangi.'));
    }
}
