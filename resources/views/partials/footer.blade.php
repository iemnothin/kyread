<footer class="site-footer mt-5 pt-5 pb-3">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="navbar-brand ky-brand mb-2" href="{{ route('home') }}">{{ config('app.name') }}</a>
                <p class="text-secondary mb-0">Platform jual beli buku dari berbagai toko: dari karya klasik sampai rilisan terbaru, semua ada di sini.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="mb-3">Layanan</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a class="text-decoration-none text-body" href="{{ route('help') }}">Bantuan</a></li>
                    <li class="mb-2"><a class="text-decoration-none text-body" href="{{ route('help') }}#pembayaran">Metode Pembayaran</a></li>
                    <li class="mb-2"><a class="text-decoration-none text-body" href="{{ route('help') }}#pengembalian">Pengembalian Dana</a></li>
                    <li class="mb-2"><a class="text-decoration-none text-body" href="{{ route('seller.dashboard') }}">Jualan di kyread</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="mb-3">Pembayaran</h6>
                <div class="row row-cols-3 g-2 logo-grid mb-3">
                    @foreach (\App\Enums\PaymentMethod::cases() as $payment)
                        <div class="col"><img src="{{ asset($payment->logo()) }}" alt="{{ $payment->label() }}" title="{{ $payment->label() }}"></div>
                    @endforeach
                </div>
                <h6 class="mb-3">Pengiriman</h6>
                <div class="row row-cols-3 g-2 logo-grid">
                    @foreach (\App\Enums\Courier::cases() as $courier)
                        <div class="col"><img src="{{ asset($courier->logo()) }}" alt="{{ $courier->label() }}" title="{{ $courier->label() }}"></div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-3">
                <h6 class="mb-3">Kontak</h6>
                <ul class="list-unstyled small text-secondary">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Kota Depok, Jawa Barat</li>
                    <li class="mb-2"><i class="bi bi-envelope me-2"></i>mail@kyreadstore.com</li>
                    <li class="mb-2"><i class="bi bi-telephone me-2"></i>+62 812 788 07</li>
                </ul>
            </div>
        </div>
        <hr>
        <p class="small text-secondary text-center mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. Dibuat dengan Laravel {{ app()->version() }}.</p>
    </div>
</footer>
