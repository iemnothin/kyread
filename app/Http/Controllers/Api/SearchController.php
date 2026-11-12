<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/** Dipakai web (/search/...) dan API (/api/v1/search/...). */
class SearchController extends Controller
{
    /**
     * Saran kata saat mengetik (dipanggil tiap huruf, di-debounce di klien).
     * Urutan: judul yang diawali kata kunci, penulis, kategori, lalu judul yang mengandung kata kunci.
     */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $like = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q);
        $base = fn () => Product::active();

        $titles = $base()->where('title', 'like', "{$like}%")->orderByDesc('sold_count')->take(5)->get(['title', 'slug'])
            ->map(fn ($p) => ['type' => 'title', 'label' => $p->title, 'value' => $p->title, 'slug' => $p->slug]);

        $authors = $base()->where('author', 'like', "%{$like}%")->select('author')->distinct()->orderBy('author')->take(3)->pluck('author')
            ->map(fn ($a) => ['type' => 'author', 'label' => $a, 'value' => $a, 'slug' => null]);

        $categories = Category::where('name', 'like', "{$like}%")->take(2)->get(['name', 'slug'])
            ->map(fn ($c) => ['type' => 'category', 'label' => $c->name, 'value' => $c->name, 'slug' => $c->slug]);

        $contains = $base()->where('title', 'like', "%{$like}%")->where('title', 'not like', "{$like}%")
            ->orderByDesc('sold_count')->take(5)->get(['title', 'slug'])
            ->map(fn ($p) => ['type' => 'title', 'label' => $p->title, 'value' => $p->title, 'slug' => $p->slug]);

        return response()->json([
            'data' => $titles->concat($authors)->concat($categories)->concat($contains)
                ->unique(fn ($s) => $s['type'].$s['label'])->take(8)->values(),
        ]);
    }

    /** Cari buku dengan foto sampul/halaman. */
    public function image(Request $request, AiService $ai): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], ['image.max' => 'Ukuran foto maksimal 5 MB.']);

        try {
            $identified = $ai->identifyBook($request->file('image'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        $products = $ai->matchProducts($identified);

        return response()->json([
            'identified' => $identified,
            'query' => $identified['title'] ?? $identified['author'] ?? ($identified['keywords'][0] ?? ''),
            'data' => ProductResource::collection($products),
        ]);
    }
}
