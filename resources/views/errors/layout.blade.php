<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::htmlLang() }}" dir="{{ \App\Support\Locales::dir() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('code') · {{ config('app.name') }}</title>
    <link href="{{ asset('img/brand.svg') }}" rel="icon" type="image/svg+xml">
    @include('partials.i18n-script')
    @vite([\App\Support\Locales::isRtl() ? 'resources/css/app-rtl.css' : 'resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    @include('partials.page-loader')
    <div class="auth-wrapper d-flex align-items-center py-5">
        <div class="container text-center" style="max-width: 520px">
            <a class="navbar-brand ky-brand justify-content-center mb-4" href="{{ url('/') }}">
                <img src="{{ asset('img/brand.svg') }}" alt=""> {{ config('app.name') }}
            </a>
            <div class="card card-soft p-4 p-md-5">
                @include('partials.lottie', ['name' => 'error', 'size' => 160, 'icon' => 'bi-emoji-frown display-3 text-ky', 'class' => 'mx-auto'])
                <div class="display-5 fw-bold mt-2">@yield('code')</div>
                <h1 class="h4 fw-bold mt-2">@yield('heading')</h1>
                <p class="text-secondary">@yield('message')</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a class="btn btn-outline-ky" href="javascript:history.back()">{{ __('Kembali') }}</a>
                    <a class="btn btn-ky" href="{{ url('/') }}">{{ __('Ke Beranda') }}</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
