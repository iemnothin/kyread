<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::htmlLang() }}" dir="{{ \App\Support\Locales::dir() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app()->environment('staging') ? '[Staging] ' : '' }}@yield('title', __('Dashboard')) · kyreadSeller</title>
    <meta name="robots" content="noindex">
    <link href="{{ asset('img/brand.svg') }}" rel="icon" type="image/svg+xml">
    @include('partials.i18n-script')
    @vite([\App\Support\Locales::isRtl() ? 'resources/css/seller-rtl.css' : 'resources/css/seller.css', 'resources/js/seller.js'])
    @stack('styles')
</head>

<body>
    @include('partials.page-loader')
    @php($store = auth()->user()->store)

    <header class="header fixed-top d-flex align-items-center" id="header">
        <div class="d-flex align-items-center justify-content-between">
            <a class="logo d-flex align-items-center" href="{{ route('seller.dashboard') }}">
                <img src="{{ asset('img/brand.svg') }}" alt="">
                <span class="brand-text d-none d-lg-block">kyreadSeller</span>
                @include('partials.env-badge')
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div>

        <div class="search-bar">
            <form class="search-form d-flex align-items-center" action="{{ route('seller.products.index') }}" method="GET">
                <input name="q" type="text" value="{{ request('q') }}" title="{{ __('Cari buku di tokomu') }}" placeholder="{{ __('Cari buku di tokomu') }}">
                <button type="submit" title="{{ __('Cari') }}"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                <li class="nav-item d-block d-lg-none">
                    <a class="nav-link nav-icon search-bar-toggle" href="#"><i class="bi bi-search"></i></a>
                </li>
                <li class="nav-item pe-2">@include('partials.locale-switcher', ['compact' => true])</li>
                @if ($store)
                    @php($toProcess = $store->orders()->where('status', 'paid')->count())
                    <li class="nav-item">
                        <a class="nav-link nav-icon" href="{{ route('seller.orders.index', ['status' => 'paid']) }}" title="{{ __('Pesanan perlu diproses') }}">
                            <i class="bi bi-bell"></i>
                            @if ($toProcess)<span class="badge bg-danger badge-number">{{ $toProcess }}</span>@endif
                        </a>
                    </li>
                @endif
                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" data-bs-toggle="dropdown" href="#">
                        <img class="rounded-circle" src="{{ $store?->logo_url ?? auth()->user()->avatar_url }}" alt="">
                        <span class="d-none d-md-block dropdown-toggle ps-2">{{ $store?->name ?? auth()->user()->display_name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <h6>{{ auth()->user()->name }}</h6>
                            <span>{{ auth()->user()->email }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        @if ($store)
                            <li><a class="dropdown-item d-flex align-items-center" href="{{ route('stores.show', $store) }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i><span>{{ __('Lihat Toko') }}</span></a></li>
                        @endif
                        <li><a class="dropdown-item d-flex align-items-center" href="{{ route('home') }}"><i class="bi bi-house"></i><span>{{ __('Kembali ke kyread') }}</span></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item d-flex align-items-center" type="submit"><i class="bi bi-box-arrow-right"></i><span>{{ __('Keluar') }}</span></button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-nav" id="sidebar-nav">
            @php($nav = fn (string $pattern) => request()->routeIs($pattern) ? 'active-page' : 'collapsed')
            @if ($store)
                <li class="nav-item"><a class="nav-link {{ $nav('seller.dashboard') }}" href="{{ route('seller.dashboard') }}"><i class="bi bi-grid"></i><span>{{ __('Dashboard') }}</span></a></li>
                <li class="nav-heading">{{ __('Toko') }}</li>
                <li class="nav-item"><a class="nav-link {{ $nav('seller.products.index') }}" href="{{ route('seller.products.index') }}"><i class="bi bi-book"></i><span>{{ __('Daftar Buku') }}</span></a></li>
                <li class="nav-item"><a class="nav-link {{ $nav('seller.products.create') }}" href="{{ route('seller.products.create') }}"><i class="bi bi-plus-square"></i><span>{{ __('Tambah Buku') }}</span></a></li>
                <li class="nav-item"><a class="nav-link {{ $nav('seller.orders.*') }}" href="{{ route('seller.orders.index') }}"><i class="bi bi-receipt"></i><span>{{ __('Pesanan') }}</span></a></li>
                <li class="nav-item"><a class="nav-link {{ $nav('seller.vouchers.*') }}" href="{{ route('seller.vouchers.index') }}"><i class="bi bi-ticket-perforated"></i><span>{{ __('Voucher') }}</span></a></li>
                <li class="nav-item"><a class="nav-link {{ $nav('seller.store.edit') }}" href="{{ route('seller.store.edit') }}"><i class="bi bi-shop"></i><span>{{ __('Profil Toko') }}</span></a></li>
                <li class="nav-heading">{{ __('Bantuan') }}</li>
                <li class="nav-item"><a class="nav-link {{ $nav('seller.faq') }}" href="{{ route('seller.faq') }}"><i class="bi bi-question-circle"></i><span>{{ __('FAQ Penjual') }}</span></a></li>
            @endif
            <li class="nav-item"><a class="nav-link collapsed" href="{{ route('home') }}"><i class="bi bi-house"></i><span>{{ __('Kembali Belanja') }}</span></a></li>
        </ul>
    </aside>

    <main class="main" id="main">
        <div class="pagetitle">
            <h1>@yield('title', __('Dashboard'))</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('seller.dashboard') }}">kyreadSeller</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>

        @foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $key => $type)
            @if (session($key))
                <div class="alert alert-{{ $type }} alert-dismissible fade show" role="alert">
                    {{ session($key) }}
                    <button class="btn-close" data-bs-dismiss="alert" type="button"></button>
                </div>
            @endif
        @endforeach
        @if ($errors->hasAny(['status', 'tracking_number']))
            <div class="alert alert-danger">{{ $errors->first('status') ?: $errors->first('tracking_number') }}</div>
        @endif

        <section class="section">
            @yield('content')
        </section>
    </main>

    <footer class="footer" id="footer">
        <div class="copyright">&copy; {{ date('Y') }} <strong><span>kyread</span></strong></div>
        <div class="credits">{{ __('Template') }} <a href="https://bootstrapmade.com/" target="_blank" rel="noopener">NiceAdmin by BootstrapMade</a></div>
    </footer>

    <a class="back-to-top d-flex align-items-center justify-content-center" href="#"><i class="bi bi-arrow-up-short"></i></a>

    @stack('scripts')
</body>

</html>
