<p align="center"><img src="kyread-logo.svg" width="320" alt="kyread logo"></p>

<h3 align="center">kyread: toko buku online multi-penjual</h3>

Pembeli bisa mencari buku, memasukkan ke keranjang, checkout dengan pilihan kurir dan metode pembayaran, lalu melacak pesanan sampai selesai dan memberi ulasan. Siapa pun bisa membuka toko di **kyreadSeller** untuk mengelola katalog buku dan memproses pesanan.

## Spesifikasi

| | |
|---|---|
| Framework | Laravel **13.x** |
| PHP | **^8.3** (memakai enum, typed class constants, readonly property) |
| Database | MySQL 8 (`db_kyread`); test memakai SQLite in-memory |
| Aset front-end | **Vite** (Bootstrap 5.3, Bootstrap Icons, ApexCharts, font Barlow dari npm, tanpa CDN) |
| UI toko | Bootstrap 5.3 + Bootstrap Icons |
| UI penjual | [NiceAdmin](https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/) + ApexCharts |
| API | REST `/api/v1`, token Laravel Sanctum |
| AI | Claude API (`anthropic-ai/sdk`), model `claude-opus-5-5` |
| Android | Kotlin + Jetpack Compose (folder [`android/`](android/README.md)) |

## Fitur

**Pembeli**
- Beranda dinamis: promo, kategori, buku terlaris, dan buku terbaru
- Katalog dengan pencarian (judul/penulis/penerbit), filter kategori & rentang harga, urutan, paginasi
- Halaman detail buku (rating, ulasan, buku serupa) dan halaman toko
- Keranjang per toko, dengan validasi stok
- Checkout: alamat (bisa disimpan ke profil), 6 kurir dengan ongkir berbasis berat, 6 metode pembayaran
- Keranjang dari beberapa toko otomatis dipecah menjadi satu pesanan per toko
- Riwayat pesanan dengan filter status, timeline status, simulasi bayar, batal, terima pesanan
- Ulasan & rating untuk pesanan yang sudah selesai
- Profil: data diri, foto, alamat utama, ganti password, hapus akun
- Login bisa memakai email **atau** username, atau **Daftar/Masuk dengan Google**
- **Saran kata** di kolom pencarian yang diperbarui setiap huruf diketik (judul, penulis, kategori)
- **Cari dengan gambar**: foto sampul buku → AI mengenali judul/penulis → dicocokkan dengan katalog
- **Tanya Kyra**: asisten AI yang merekomendasikan buku dari katalog dan menjawab pertanyaan belanja
- Pesan validasi & halaman error (403/404/419/429/500/503) berbahasa Indonesia

**Penjual (kyreadSeller)**
- Buka toko langsung dari akun yang sama
- Dashboard: pendapatan, jumlah pesanan, buku terjual, grafik penjualan 14 hari, buku terlaris, stok menipis
- CRUD buku: sampul, kategori, penulis, penerbit, ISBN, halaman, tahun, harga, berat, stok, aktif/nonaktif
- Kelola pesanan: proses → kirim (wajib nomor resi) → selesai, atau tolak/batalkan
- Profil toko (nama, kota, logo, deskripsi)

**Aturan bisnis**
- Stok dikunci (`lockForUpdate`) saat checkout supaya tidak terjadi overselling
- Pesanan yang dibatalkan otomatis mengembalikan stok
- Pesanan yang belum dibayar lebih dari 24 jam dibatalkan otomatis (`php artisan orders:expire`, dijadwalkan tiap jam)
- Penjual tidak bisa membeli buku dari tokonya sendiri. Akses pesanan dan buku dibatasi lewat Policy.
- Judul, harga, dan sampul disalin ke `order_items`, jadi riwayat tetap utuh walaupun bukunya dihapus

> Pembayaran masih **disimulasikan** (tombol "Saya Sudah Bayar"), belum terhubung ke payment gateway.

## Instalasi Lokal

```bash
git clone https://github.com/iemnothin/kyread.git
cd kyread
composer install
cp .env.example .env          # sesuaikan DB_USERNAME / DB_PASSWORD
php artisan key:generate

# buat database lebih dulu, misalnya:
# mysql -uroot -e "CREATE DATABASE db_kyread CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

php artisan migrate --seed
php artisan storage:link
npm install && npm run build  # atau `npm run dev` saat pengembangan
php artisan serve
```

### Fitur opsional (isi di `.env`)

| Fitur | Variabel | Catatan |
|---|---|---|
| Masuk dengan Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Buat OAuth client **Web application** di Google Cloud Console dengan redirect URI `{APP_URL}/auth/google/callback`. Client ID yang sama dipakai aplikasi Android (`googleWebClientId`). |
| Cari dengan gambar & Tanya Kyra | `ANTHROPIC_API_KEY` | Kunci hanya disimpan di server; web dan Android memanggil endpoint kyread. |

Kalau variabel kosong, tombol dan menu fitur tersebut otomatis disembunyikan.

Buka http://localhost:8000.

Supaya pembatalan otomatis berjalan, jalankan scheduler: `php artisan schedule:work` (development) atau cron `* * * * * php artisan schedule:run`.

### Akun demo (password: `password`)

| Peran | Email | Username |
|---|---|---|
| Pembeli (punya riwayat pesanan) | buyer@kyread.test | `pembeli` |
| Penjual: Kyread Official Store | seller@kyread.test | `kyreadofficial` |
| Penjual: Pojok Baca Bandung | seller2@kyread.test | `pojokbaca` |

Seeder mengisi 8 kategori, 25 buku, dan 18 pesanan contoh dalam 14 hari terakhir, sehingga dashboard langsung terisi. Data buku hanya contoh. Penulis yang tidak diketahui ditulis "Tim Penulis".

## Deploy Staging

Staging: **https://kyread-staging.skyvora.id** (VPS, Nginx + PHP-FPM 8.5 + MySQL 8.4).

```bash
deploy/scripts/deploy.sh      # butuh alias SSH "skyvora" dan working tree yang bersih
```

Script mengunggah `git archive HEAD`, lalu menjalankan `deploy/scripts/setup-server.sh` di server. Script itu menyiapkan database & `.env` (hanya sekali), `composer install`, migrasi, cache, cron scheduler, serta Nginx + SSL certbot (hanya sekali). Rilis disimpan di `/var/www/kyread-staging/releases/<rev>` dengan symlink `current`. Untuk rollback, arahkan `current` ke rilis sebelumnya lalu jalankan `systemctl reload php8.5-fpm`. Data demo hanya di-seed saat database masih kosong.

## Testing

```bash
php artisan test
```

Feature test mencakup storefront, autentikasi, siklus pesanan lengkap (keranjang → checkout → bayar → proses → kirim → selesai → ulasan), pemecahan pesanan per toko, stok tidak cukup, pembatalan & pengembalian stok, kedaluwarsa pesanan, serta otorisasi penjual.

## Struktur Utama

```
app/
├── Enums/            OrderStatus, Courier (tarif & ongkir), PaymentMethod
├── Http/Controllers/ Storefront: Home, Catalog, Cart, Checkout, Order, Profile, Auth
│   └── Seller/       Dashboard, Product, Order, Store
├── Http/Middleware/  EnsureUserHasStore (alias "seller")
├── Models/           User, Store, Category, Product, CartItem, Order, OrderItem, Review
├── Policies/         ProductPolicy, OrderPolicy
└── Services/         CheckoutService (buat pesanan + kunci stok), OrderService (transisi status)
```

## Lisensi

[MIT](LICENSE)
