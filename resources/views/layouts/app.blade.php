<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::htmlLang() }}" dir="{{ \App\Support\Locales::dir() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app()->environment('staging') ? '[Staging] ' : '' }}@hasSection('title')@yield('title') · @endif{{ config('app.name') }} — {{ __('Toko Buku Online') }}</title>
    <meta name="description" content="@yield('description', __('kyread: toko buku online. Novel, komik, biografi, dan banyak lagi dengan pengiriman ke seluruh Indonesia.'))">

    @include('partials.meta')
    @include('partials.i18n-script')
    @vite([\App\Support\Locales::isRtl() ? 'resources/css/app-rtl.css' : 'resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="d-flex flex-column min-vh-100">
    @include('partials.page-loader')
    @include('partials.header')

    <main class="flex-grow-1 py-4">
        <div class="container">
            @include('partials.alerts')
            @yield('content')
        </div>
    </main>

    @include('partials.footer')
    @include('partials.image-search-modal')

    @if ($aiEnabled && ! request()->routeIs('assistant'))
        <a class="btn btn-ky rounded-pill shadow assistant-fab" href="{{ route('assistant') }}" title="{{ __('Tanya Kyra, asisten buku AI') }}">
            <i class="bi bi-stars me-1"></i><span class="d-none d-sm-inline">{{ __('Tanya Kyra') }}</span>
        </a>
    @endif

    @stack('scripts')
</body>

</html>
