<meta name="theme-color" content="#43aaff">
<link href="{{ asset('img/brand.svg') }}" rel="icon" type="image/svg+xml">
<link href="{{ asset('img/apple-touch-icon.png') }}" rel="apple-touch-icon">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="@yield('title', config('app.name').' — '.__('Toko Buku Online'))">
<meta property="og:description" content="@yield('description', __('Toko buku online: novel, komik, biografi, dan banyak lagi.'))">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="@yield('og_image', asset('img/promo-banner/banner1.jpg'))">
<link href="{{ url()->current() }}" rel="canonical">
