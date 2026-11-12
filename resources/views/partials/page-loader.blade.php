{{-- Loading screen: tampil saat halaman dimuat dan saat berpindah halaman (lihat initPageLoader di common.js). --}}
<div class="page-loader" id="page-loader" role="status" aria-live="polite">
    {{-- SVG disematkan agar logo langsung tampil meski jaringan lambat. --}}
    {!! str_replace('<svg ', '<svg class="loader-logo" aria-hidden="true" ', file_get_contents(public_path('img/brand.svg'))) !!}
    <div class="bar"></div>
    <span class="label">Memuat…</span>
</div>
<noscript><style>.page-loader { display: none; }</style></noscript>
