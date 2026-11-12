<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }} — Toko Buku Online</title>
    <meta name="description" content="@yield('description', 'kyread: toko buku online. Novel, komik, biografi, dan banyak lagi dengan pengiriman ke seluruh Indonesia.')">

    <link href="{{ asset('pos/assets/img/brand.svg') }}" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,500;0,600;0,700;1,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('pos/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body class="d-flex flex-column min-vh-100">
    @include('partials.header')

    <main class="flex-grow-1 py-4">
        <div class="container">
            @include('partials.alerts')
            @yield('content')
        </div>
    </main>

    @include('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Konfirmasi untuk aksi berisiko: <form data-confirm="Yakin?">
        document.addEventListener('submit', (e) => {
            const msg = e.target.dataset.confirm;
            if (msg && !confirm(msg)) e.preventDefault();
        });
    </script>
    @stack('scripts')
</body>

</html>
