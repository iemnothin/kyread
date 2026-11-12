@extends('layouts.seller')

@section('title', 'FAQ Penjual')
@section('breadcrumb')<li class="breadcrumb-item active">FAQ</li>@endsection

@section('content')
    @php
        $faqs = [
            'Bagaimana alur pesanan di kyreadSeller?' => 'Pembeli checkout → membayar (status "Dibayar") → kamu klik "Proses Pesanan" → kemas lalu isi nomor resi dan klik "Kirim Pesanan" → pembeli menekan "Pesanan Diterima" dan pesanan selesai.',
            'Kapan stok buku berkurang?' => 'Stok berkurang saat pembeli membuat pesanan, sehingga buku tidak bisa dibeli melebihi stok. Jika pesanan dibatalkan (oleh pembeli, penjual, atau otomatis setelah 24 jam tidak dibayar), stok dikembalikan.',
            'Bagaimana ongkos kirim dihitung?' => 'Ongkir = tarif kurir per kg × total berat buku (dibulatkan ke atas per kilogram). Pastikan berat buku diisi dengan benar.',
            'Bisakah saya menyembunyikan buku sementara?' => 'Bisa. Matikan opsi "Tampilkan di katalog" di halaman edit buku. Buku tidak akan muncul di pencarian dan tidak bisa dibeli.',
            'Apakah buku yang sudah dihapus memengaruhi riwayat pesanan?' => 'Tidak. Judul, harga, dan sampul disalin ke data pesanan, jadi riwayat tetap utuh.',
        ];
    @endphp
    <div class="card">
        <div class="card-body pt-3">
            <div class="accordion accordion-flush" id="faq">
                @foreach ($faqs as $q => $a)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq-{{ $loop->index }}" type="button">{{ $q }}</button>
                        </h2>
                        <div class="accordion-collapse collapse" id="faq-{{ $loop->index }}" data-bs-parent="#faq">
                            <div class="accordion-body">{{ $a }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
