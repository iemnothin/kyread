<header class="site-header sticky-top">
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container">
            <a class="navbar-brand ky-brand" href="{{ route('home') }}">
                <img src="{{ asset('img/brand.svg') }}" alt="">
                <span>{{ config('app.name') }}</span>
            </a>

            <div class="d-flex align-items-center gap-3 d-lg-none">
                @auth
                    <a class="header-icon" href="{{ route('notifications.index') }}" aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                        @if ($unreadCount > 0)
                            <span class="badge rounded-pill bg-danger">{{ $unreadCount }}</span>
                        @endif
                    </a>
                    <a class="header-icon" href="{{ route('cart.index') }}" aria-label="Keranjang">
                        <i class="bi bi-bag"></i>
                        @if ($cartCount > 0)
                            <span class="badge rounded-pill bg-danger">{{ $cartCount }}</span>
                        @endif
                    </a>
                @endauth
                <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#mainNav" type="button"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="mainNav">
                <form class="search-box d-flex flex-grow-1 mx-lg-4 my-3 my-lg-0 position-relative" action="{{ route('books.index') }}" method="GET" role="search"
                    data-suggest-url="{{ route('search.suggest') }}">
                    <input class="form-control" name="q" type="search" value="{{ request('q') }}" autocomplete="off"
                        placeholder="Cari judul, penulis, atau penerbit…" aria-label="Cari buku" role="combobox"
                        aria-autocomplete="list" aria-expanded="false" aria-controls="searchSuggestions">
                    @if ($aiEnabled)
                        <button class="btn btn-light border px-3 rounded-0" type="button" data-image-search title="Cari dengan foto sampul" aria-label="Cari dengan gambar">
                            <i class="bi bi-camera"></i>
                        </button>
                    @endif
                    <button class="btn btn-ky px-3" type="submit" aria-label="Cari"><i class="bi bi-search"></i></button>
                    <ul class="dropdown-menu w-100 shadow-sm border-0 search-suggestions" id="searchSuggestions" role="listbox"></ul>
                </form>

                <ul class="navbar-nav align-items-lg-center gap-lg-3 ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('books.*') ? 'fw-semibold text-ky' : '' }}" href="{{ route('books.index') }}">Katalog</a>
                    </li>
                    @if ($aiEnabled)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('assistant') ? 'fw-semibold text-ky' : '' }}" href="{{ route('assistant') }}">
                                <i class="bi bi-stars text-ky"></i> Tanya Kyra
                            </a>
                        </li>
                    @endif
                    @auth
                        <li class="nav-item d-none d-lg-block">
                            <a class="header-icon" href="{{ route('wishlist.index') }}" title="Wishlist">
                                <i class="bi bi-heart"></i>
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <a class="header-icon" href="{{ route('notifications.index') }}" title="Notifikasi">
                                <i class="bi bi-bell"></i>
                                @if ($unreadCount > 0)
                                    <span class="badge rounded-pill bg-danger">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <a class="header-icon" href="{{ route('cart.index') }}" title="Keranjang">
                                <i class="bi bi-bag"></i>
                                @if ($cartCount > 0)
                                    <span class="badge rounded-pill bg-danger">{{ $cartCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <a class="header-icon" href="{{ route('seller.dashboard') }}" title="kyreadSeller">
                                <i class="bi bi-shop-window"></i>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                <img class="rounded-circle" src="{{ auth()->user()->avatar_url }}" alt="" width="32" height="32" style="object-fit: cover">
                                <span class="fw-semibold">{{ auth()->user()->display_name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-2 text-ky"></i>Pesanan Saya</a></li>
                                <li><a class="dropdown-item" href="{{ route('wishlist.index') }}"><i class="bi bi-heart me-2 text-ky"></i>Wishlist</a></li>
                                <li><a class="dropdown-item" href="{{ route('notifications.index') }}"><i class="bi bi-bell me-2 text-ky"></i>Notifikasi @if ($unreadCount > 0)<span class="badge bg-danger ms-1">{{ $unreadCount }}</span>@endif</a></li>
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2 text-ky"></i>Edit Profil</a></li>
                                <li><a class="dropdown-item" href="{{ route('seller.dashboard') }}"><i class="bi bi-shop me-2 text-ky"></i>{{ auth()->user()->store ? 'Toko Saya' : 'Buka Toko' }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item"><a class="btn btn-outline-ky btn-sm px-3" href="{{ route('login') }}">Masuk</a></li>
                        <li class="nav-item mt-2 mt-lg-0"><a class="btn btn-ky btn-sm px-3" href="{{ route('register') }}">Daftar</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>
</header>
