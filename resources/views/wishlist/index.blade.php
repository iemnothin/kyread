@extends('layouts.app')

@section('title', __('Wishlist'))

@section('content')
    <div class="d-flex align-items-baseline gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-heart-fill text-danger"></i> {{ __('Wishlist') }}</h1>
        <small class="text-secondary">{{ trans_choice(':count buku', $products->total()) }}</small>
    </div>

    @if ($products->isEmpty())
        <div class="card card-soft">
            <div class="card-body text-center py-5">
                <i class="bi bi-heart display-4 text-secondary"></i>
                <p class="mt-3 mb-1 fw-semibold">{{ __('Wishlist kamu masih kosong') }}</p>
                <p class="text-secondary small">{{ __('Tekan ikon hati pada buku yang ingin kamu simpan untuk nanti.') }}</p>
                <a class="btn btn-ky" href="{{ route('books.index') }}">{{ __('Jelajahi Katalog') }}</a>
            </div>
        </div>
    @else
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
            @foreach ($products as $product)
                <div class="col">@include('partials.product-card')</div>
            @endforeach
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endsection
