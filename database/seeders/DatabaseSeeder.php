<?php

namespace Database\Seeders;

use App\Enums\Courier;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\VoucherType;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Akun demo. Semua password: "password". */
    public function run(): void
    {
        mt_srand(2026);

        $categories = collect([
            'Novel' => 'novel', 'Biografi' => 'biography', 'Komik' => 'comics', 'Majalah' => 'magazine',
            'Kamus' => 'dictionary', 'Naskah' => 'script', 'Manga' => 'manga', 'Ensiklopedia' => 'encyclopedia',
        ])->map(fn (string $icon, string $name) => Category::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => "img/categories/{$icon}.png",
        ]));

        $seller = User::factory()->create([
            'name' => 'Kyread Official', 'username' => 'kyreadofficial', 'email' => 'seller@kyread.test',
            'city' => 'Depok', 'province' => 'Jawa Barat',
        ]);
        $seller2 = User::factory()->create([
            'name' => 'Pojok Baca', 'username' => 'pojokbaca', 'email' => 'seller2@kyread.test',
            'city' => 'Bandung', 'province' => 'Jawa Barat',
        ]);
        $buyer = User::factory()->create([
            'name' => 'Pembeli Demo', 'nickname' => 'Demo', 'username' => 'pembeli', 'email' => 'buyer@kyread.test',
            'street' => 'Jl. Margonda Raya No. 100, Pondok Cina, Beji', 'city' => 'Depok',
            'province' => 'Jawa Barat', 'zipcode' => '16424', 'phone' => '081234567890',
        ]);

        $official = Store::forceCreate([
            'user_id' => $seller->id, 'name' => 'Kyread Official Store', 'slug' => 'kyread-official-store',
            'description' => 'Toko resmi kyread. Buku original, dikemas rapi dengan bubble wrap.',
            'city' => 'Depok', 'phone' => '081278807000',
        ]);
        $pojok = Store::forceCreate([
            'user_id' => $seller2->id, 'name' => 'Pojok Baca Bandung', 'slug' => 'pojok-baca-bandung',
            'description' => 'Novel, fiksi terjemahan, dan bacaan anak. Kirim setiap hari kerja.',
            'city' => 'Bandung', 'phone' => '081278917000',
        ]);

        // [judul, penulis, penerbit, kategori, harga, file cover, toko]
        $books = [
            ['00.00', 'Ameylia Falensia', 'Gramedia Pustaka Utama', 'Novel', 73_000, '00-00-karya-ameylia-falensia', $official],
            ['Bedebah di Ujung Tanduk', 'Tere Liye', 'Sabak Grip', 'Novel', 99_000, 'bedebah-di-ujung-tanduk', $official],
            ['Black Showman dan Pembunuhan di Kota Tak Bernama', 'Keigo Higashino', 'Gramedia Pustaka Utama', 'Novel', 125_000, 'black-showman-dan-pembunuhan-di-kota-tak-bernama', $pojok],
            ['Cantik Itu Luka', 'Eka Kurniawan', 'Gramedia Pustaka Utama', 'Novel', 125_000, 'cantik-itu-luka', $official],
            ['Di Tanah Lada', 'Ziggy Zezsyazeoviennazabrizkie', 'Gramedia Pustaka Utama', 'Novel', 85_000, 'di-tanah-lada', $pojok],
            ['Für Immer Dein Ian', 'Tim Penulis', 'Penerbit Contoh', 'Novel', 79_000, 'fur-immer-dein-ian', $pojok],
            ["Harry Potter and the Philosopher's Stone", 'J.K. Rowling', 'Bloomsbury', 'Novel', 189_000, 'harry-potter-and-the-philosopher-s-stone', $official],
            ['Heartbreak Motel', 'Ika Natassa', 'Gramedia Pustaka Utama', 'Novel', 110_000, 'heartbreak-motel', $official],
            ['I Think I Love You', 'Cha Mirae', 'Penerbit Haru', 'Novel', 89_000, 'i-think-i-love-you-cha-mirae', $pojok],
            ['Kita Pergi Hari Ini', 'Ziggy Zezsyazeoviennazabrizkie', 'Gramedia Pustaka Utama', 'Novel', 75_000, 'kita-pergi-hari-ini', $pojok],
            ['Bulan', 'Tere Liye', 'Gramedia Pustaka Utama', 'Novel', 105_000, 'moon-atau-bulan', $official],
            ['Perpustakaan Tengah Malam', 'Matt Haig', 'Gramedia Pustaka Utama', 'Novel', 115_000, 'perpustakaan-tengah-malam', $official],
            ['Puya ke Puya', 'Faisal Oddang', 'Kepustakaan Populer Gramedia', 'Novel', 70_000, 'puya-ke-puya', $pojok],
            ['Ramai yang Dulu Kita Bawa Pergi', 'Tim Penulis', 'Penerbit Contoh', 'Novel', 68_000, 'ramai-yang-dulu-kita-bawa-pergi', $pojok],
            ['Sagaras', 'Tere Liye', 'Gramedia Pustaka Utama', 'Novel', 99_000, 'sagaras', $official],
            ['Selamat Tinggal', 'Tere Liye', 'Gramedia Pustaka Utama', 'Novel', 89_000, 'selamat-tinggal', $official],
            ['Terpikat', 'Tim Penulis', 'Penerbit Contoh', 'Novel', 72_000, 'terpikat', $pojok],
            ['The Age Between Us', 'Tim Penulis', 'Penerbit Contoh', 'Novel', 82_000, 'the-age-between-us', $pojok],
            ["The Chronicles of Narnia #1: The Magician's Nephew", 'C.S. Lewis', 'Gramedia Pustaka Utama', 'Novel', 65_000, 'the-chronicles-of-narnia-1', $official],
            ['The Chronicles of Narnia #3: The Horse and His Boy', 'C.S. Lewis', 'Gramedia Pustaka Utama', 'Novel', 65_000, 'the-chronicles-of-narnia-3-the-horse-and-his-boy', $official],
            ['Twenty Four Eyes', 'Sakae Tsuboi', 'Penerbit Contoh', 'Novel', 73_000, 'twenty-four-eyes', $pojok],
            ['Where Stories Begin', 'Tim Penulis', 'Penerbit Contoh', 'Novel', 69_000, 'where-stories-begin', $pojok],
            ['Wingit', 'Sara Wijayanto', 'Gramedia Pustaka Utama', 'Biografi', 98_000, 'wingit', $official],
            ['Yang Bertahan dan Binasa Perlahan', 'Okky Madasari', 'Gramedia Pustaka Utama', 'Novel', 78_000, 'yang-bertahan-dan-binasa-perlahan', $official],
            ['Ten Years Challenge', 'Tim Penulis', 'Penerbit Contoh', 'Novel', 79_000, 'youngadult-ten-years-challenge', $pojok],
        ];

        $products = collect($books)->map(fn (array $b) => Product::forceCreate([
            'store_id' => $b[6]->id,
            'category_id' => $categories[$b[3]]->id,
            'title' => $b[0],
            'slug' => Str::slug($b[0]),
            'author' => $b[1],
            'publisher' => $b[2],
            'pages' => mt_rand(180, 520),
            'published_year' => mt_rand(2015, 2025),
            'description' => "\"{$b[0]}\" karya {$b[1]}. Buku original terbitan {$b[2]}, kondisi baru dan tersegel. "
                .'Cocok untuk menemani waktu santaimu. Pesanan dikemas aman dengan bubble wrap dan kardus.',
            'price' => $b[4],
            'weight' => mt_rand(25, 60) * 10,
            'stock' => mt_rand(8, 60),
            'image' => "img/books/{$b[5]}.jpg",
            'created_at' => now()->subDays(mt_rand(0, 60)),
        ]));

        // Sebagian buku sedang diskon (harga coret 15–35% di atas harga jual).
        $products->each(function (Product $product, int $i): void {
            if ($i % 4 === 1) {
                $markup = [1.15, 1.25, 1.35][$i % 3];
                $product->forceFill(['compare_at_price' => (int) (ceil($product->price * $markup / 1000) * 1000)])->save();
            }
        });

        $official->vouchers()->create([
            'code' => 'KYREAD10', 'type' => VoucherType::Percent, 'value' => 10,
            'min_spend' => 75000, 'max_discount' => 20000, 'expires_at' => now()->addMonths(3),
        ]);
        $pojok->vouchers()->create([
            'code' => 'POJOKHEMAT', 'type' => VoucherType::Fixed, 'value' => 15000,
            'min_spend' => 100000, 'quota' => 100,
        ]);

        $buyer->wishlist()->attach($products->random(3)->pluck('id'));

        $this->seedOrderHistory($buyer, $products);
    }

    /** Riwayat pesanan 14 hari terakhir agar dashboard penjual & ulasan langsung terisi. */
    private function seedOrderHistory(User $buyer, $products): void
    {
        $statuses = [
            OrderStatus::Completed, OrderStatus::Completed, OrderStatus::Completed, OrderStatus::Completed,
            OrderStatus::Shipped, OrderStatus::Processing, OrderStatus::Paid, OrderStatus::Pending, OrderStatus::Cancelled,
        ];

        foreach (range(1, 18) as $i) {
            $status = $statuses[array_rand($statuses)];
            $createdAt = now()->subDays(mt_rand(0, 13))->setTime(mt_rand(8, 21), mt_rand(0, 59));
            $storeProducts = $products->groupBy('store_id')->random();
            $picked = $storeProducts->random(mt_rand(1, min(3, $storeProducts->count())));
            $courier = Courier::cases()[array_rand(Courier::cases())];

            $lines = $picked->map(fn (Product $p) => ['product' => $p, 'qty' => mt_rand(1, 2)]);
            $subtotal = $lines->sum(fn ($l) => $l['product']->price * $l['qty']);
            $shipping = $courier->costFor($lines->sum(fn ($l) => $l['product']->weight * $l['qty']));

            if ($status === OrderStatus::Pending) {
                $createdAt = now()->subHours(mt_rand(1, 10));
            }

            $order = Order::forceCreate([
                'code' => Order::generateCode(),
                'user_id' => $buyer->id,
                'store_id' => $picked->first()->store_id,
                'status' => $status,
                'recipient_name' => $buyer->name,
                'recipient_phone' => $buyer->phone,
                'street' => $buyer->street,
                'city' => $buyer->city,
                'province' => $buyer->province,
                'zipcode' => $buyer->zipcode,
                'courier' => $courier,
                'tracking_number' => in_array($status, [OrderStatus::Shipped, OrderStatus::Completed]) ? 'RESI'.mt_rand(10_000_000, 99_999_999) : null,
                'payment_method' => PaymentMethod::cases()[array_rand(PaymentMethod::cases())],
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'total' => $subtotal + $shipping,
                'paid_at' => $status->countsAsRevenue() ? $createdAt->copy()->addMinutes(30) : null,
                'shipped_at' => in_array($status, [OrderStatus::Shipped, OrderStatus::Completed]) ? $createdAt->copy()->addDay() : null,
                'completed_at' => $status === OrderStatus::Completed ? $createdAt->copy()->addDays(3) : null,
                'cancelled_at' => $status === OrderStatus::Cancelled ? $createdAt->copy()->addHours(5) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'title' => $line['product']->title,
                    'image' => $line['product']->image,
                    'price' => $line['product']->price,
                    'quantity' => $line['qty'],
                    'subtotal' => $line['product']->price * $line['qty'],
                ]);

                if ($status !== OrderStatus::Cancelled) {
                    $line['product']->increment('sold_count', $line['qty']);
                }

                if ($status === OrderStatus::Completed) {
                    $order->reviews()->firstOrCreate(['product_id' => $line['product']->id], [
                        'user_id' => $buyer->id,
                        'rating' => mt_rand(4, 5),
                        'comment' => collect([
                            'Bukunya original, pengiriman cepat. Mantap!',
                            'Packing rapi banget, recommended seller.',
                            'Ceritanya bagus, nggak nyesel beli.',
                            'Sesuai deskripsi, terima kasih kak.',
                        ])->random(),
                    ]);
                }
            }
        }
    }
}
