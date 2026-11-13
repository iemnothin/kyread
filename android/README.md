# kyread untuk Android

Aplikasi native (Kotlin + Jetpack Compose, Material 3) untuk **pembeli** kyread. Panel penjual tetap di web (kyreadSeller); menu Akun membukanya di browser.

## Fitur

- Beranda: banner promo, kategori, terlaris, baru masuk (tarik untuk refresh)
- Cari: saran kata per huruf, filter kategori, urutan, scroll tanpa batas
- **Cari dengan gambar**: foto sampul dari kamera atau galeri, dikenali AI di server
- Detail buku, bagikan link, tambah ke keranjang / beli sekarang
- Keranjang per toko, checkout (alamat, kurir dengan ongkir otomatis, metode bayar)
- Pesanan: filter status, bayar (simulasi), batal, terima, ulasan bintang
- **Tanya Kyra**: chat asisten AI dengan kartu rekomendasi buku
- Masuk/daftar dengan email/username atau **Google** (Credential Manager)
- Edit profil & alamat, splash screen, ikon adaptif, mode gelap, deep link `https://kyread-staging.skyvora.id/books/{slug}`

## Kebutuhan

- Android Studio (JDK 17+), Android SDK 36
- Min. Android 8.0 (API 26)

## Konfigurasi (`gradle.properties` atau `-P`)

| Properti | Default | Keterangan |
|---|---|---|
| `kyreadApiUrl` | `https://kyread-staging.skyvora.id/api/v1/` | Server lokal dari emulator: `http://10.0.2.2:8000/api/v1/` |
| `googleWebClientId` | kosong | **Web client ID** OAuth Google (sama dengan `GOOGLE_CLIENT_ID` di server). Kosong = tombol Google disembunyikan. Untuk rilis, daftarkan juga client **Android** (package `id.skyvora.kyread` + SHA-1 keystore) di project Google Cloud yang sama. |

## Build

```bash
cd android
./gradlew assembleDebug            # app/build/outputs/apk/debug/app-debug.apk
./gradlew installDebug             # pasang ke emulator/HP yang terhubung
```

Rilis bertanda tangan: buat `android/keystore.properties` (tidak di-commit):

```properties
storeFile=kyread-release.jks
storePassword=...
keyAlias=kyread
keyPassword=...
```

lalu `./gradlew assembleRelease` (atau `bundleRelease` untuk Play Store).

## Struktur

```
app/src/main/java/id/skyvora/kyread/
├── data/        ApiClient (OkHttp + kotlinx.serialization), KyreadRepository, TokenStore (DataStore), Models
└── ui/
    ├── KyreadAppUi.kt     navigasi + bottom bar
    ├── components/        BookCard, format Rupiah/tanggal, util gambar
    └── screens/           home, search, product, cart, checkout, orders, assistant, auth, account
```
