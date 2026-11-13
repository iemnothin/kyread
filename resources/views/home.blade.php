@extends('layouts.app')

@section('content')
    {{-- Promo --}}
    <div class="carousel slide promo-carousel rounded-4 overflow-hidden shadow-sm" id="promo" data-bs-ride="carousel">
        <div class="carousel-indicators">
            @foreach (range(1, 8) as $i)
                <button class="{{ $i === 1 ? 'active' : '' }}" data-bs-target="#promo" data-bs-slide-to="{{ $i - 1 }}" type="button" aria-label="{{ __('Promo :n', ['n' => $i]) }}"></button>
            @endforeach
        </div>
        <div class="carousel-inner">
            @foreach (range(1, 8) as $i)
                <div class="carousel-item {{ $i === 1 ? 'active' : '' }}">
                    <img class="d-block w-100" src="{{ asset("img/promo-banner/banner{$i}.jpg") }}" alt="{{ __('Promo :n', ['n' => $i]) }}">
                </div>
            @endforeach
        </div>
        <button class="carousel-control-prev" data-bs-target="#promo" data-bs-slide="prev" type="button"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" data-bs-target="#promo" data-bs-slide="next" type="button"><span class="carousel-control-next-icon"></span></button>
    </div>

    {{-- Kategori --}}
    <h2 class="section-title mt-5 mb-3">{{ __('Kategori') }}</h2>
    <div class="row row-cols-4 row-cols-lg-8 g-2 g-md-3">
        @foreach ($categories as $category)
            <div class="col">
                <a class="category-chip" href="{{ route('books.index', ['category' => $category->slug]) }}">
                    <img src="{{ $category->icon_url }}" alt="">
                    <span class="fw-semibold small text-center">{{ $category->name }}</span>
                    <span class="text-secondary" style="font-size: .7rem">{{ trans_choice(':count buku', $category->products_count) }}</span>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Terlaris --}}
    <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
        <h2 class="section-title mb-0">{{ __('Terlaris') }}</h2>
        <a class="small fw-semibold text-decoration-none" href="{{ route('books.index', ['sort' => 'bestseller']) }}">{{ __('Lihat semua') }} <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
        @forelse ($bestsellers as $product)
            <div class="col">@include('partials.product-card')</div>
        @empty
            <p class="text-secondary">{{ __('Belum ada buku yang dijual.') }}</p>
        @endforelse
    </div>

    {{-- Diskon --}}
    @if ($deals->isNotEmpty())
        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h2 class="section-title mb-0"><i class="bi bi-lightning-charge-fill text-danger"></i> {{ __('Sedang Diskon') }}</h2>
            <a class="small fw-semibold text-decoration-none" href="{{ route('books.index', ['promo' => 1, 'sort' => 'discount']) }}">{{ __('Lihat semua') }} <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
            @foreach ($deals as $product)
                <div class="col">@include('partials.product-card')</div>
            @endforeach
        </div>
    @endif

    {{-- Terbaru --}}
    <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
        <h2 class="section-title mb-0">{{ __('Baru Masuk') }}</h2>
        <a class="small fw-semibold text-decoration-none" href="{{ route('books.index') }}">{{ __('Lihat semua') }} <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
        @foreach ($latest as $product)
            <div class="col">@include('partials.product-card')</div>
        @endforeach
    </div>

    {{-- CTA penjual --}}
    <div class="card card-soft mt-5 overflow-hidden">
        <div class="card-body p-4 p-md-5 d-md-flex align-items-center justify-content-between" style="background: linear-gradient(120deg, #43aaff, #1f8ae6); color: #fff">
            <div>
                <h3 class="fw-bold mb-1">{{ __('Punya koleksi buku untuk dijual?') }}</h3>
                <p class="mb-md-0 opacity-75">{{ __('Buka toko gratis di kyreadSeller dan jangkau pembaca di seluruh Indonesia.') }}</p>
            </div>
            <a class="btn btn-light fw-semibold px-4" href="{{ route('seller.dashboard') }}">{{ __('Mulai Jualan') }}</a>
        </div>
    </div>
@endsection
