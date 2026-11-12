@extends('layouts.app')

@section('title', 'Bantuan')

@section('content')
    <div class="mx-auto" style="max-width: 820px">
        <h1 class="h3 fw-bold mb-4">Pusat Bantuan</h1>

        @php
            $faqs = [
                'Cara berbelanja' => [
                    'Bagaimana cara membeli buku?' => 'Cari buku lewat kolom pencarian atau kategori, klik "Tambah ke Keranjang" atau "Beli Sekarang", lalu lanjutkan ke checkout untuk memilih alamat, kurir, dan metode pembayaran.',
                    'Kenapa pesanan saya terpecah menjadi beberapa?' => 'Jika keranjang berisi buku dari beberapa toko, pesanan dipisah per toko karena setiap toko mengirim dari lokasi berbeda.',
                ],
                'pembayaran' => [
                    'Metode pembayaran apa saja yang tersedia?' => 'Virtual account BCA, BNI, BRI, Mandiri, kartu kredit Mastercard, dan PayPal.',
                    'Berapa lama batas waktu pembayaran?' => 'Pesanan harus dibayar dalam 24 jam. Lewat dari itu pesanan dibatalkan otomatis dan stok dikembalikan.',
                ],
                'pengembalian' => [
                    'Bisakah saya membatalkan pesanan?' => 'Pesanan yang belum dibayar bisa kamu batalkan sendiri. Pesanan yang sudah dibayar dapat dibatalkan oleh penjual sebelum dikirim, dan dana akan dikembalikan.',
                    'Bagaimana jika buku yang diterima rusak?' => 'Hubungi penjual melalui nomor yang tertera di halaman toko atau kontak kami di mail@kyreadstore.com sebelum menekan tombol "Pesanan Diterima".',
                ],
            ];
        @endphp

        @foreach ($faqs as $section => $items)
            <h2 class="h5 fw-bold text-ky mt-4 text-capitalize" id="{{ \Illuminate\Support\Str::slug($section) }}">{{ $section }}</h2>
            <div class="accordion" id="acc-{{ $loop->index }}">
                @foreach ($items as $q => $a)
                    @php($id = 'faq-'.$loop->parent->index.'-'.$loop->index)
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" type="button">{{ $q }}</button>
                        </h3>
                        <div class="accordion-collapse collapse" id="{{ $id }}" data-bs-parent="#acc-{{ $loop->parent->index }}">
                            <div class="accordion-body">{{ $a }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endsection
