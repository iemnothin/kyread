@extends('layouts.app')

@section('title', $store->name)

@section('content')
    <div class="card card-soft mb-4">
        <div class="card-body d-md-flex align-items-center gap-4 p-4">
            <img class="rounded-circle mb-3 mb-md-0" src="{{ $store->logo_url }}" alt="" width="88" height="88" style="object-fit: cover">
            <div class="flex-grow-1">
                <h1 class="h3 fw-bold mb-1">{{ $store->name }}</h1>
                <div class="small text-secondary mb-2">
                    <i class="bi bi-geo-alt"></i> {{ $store->city }} · <i class="bi bi-book"></i> {{ $store->products_count }} buku ·
                    Bergabung {{ $store->created_at->translatedFormat('F Y') }}
                </div>
                @if ($store->description)<p class="mb-0">{{ $store->description }}</p>@endif
            </div>
            <form class="search-box d-flex mt-3 mt-md-0" action="{{ route('stores.show', $store) }}" method="GET">
                <input class="form-control" name="q" type="search" value="{{ request('q') }}" placeholder="Cari di toko ini">
                <button class="btn btn-ky" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
        @forelse ($products as $product)
            <div class="col">@include('partials.product-card')</div>
        @empty
            <p class="text-secondary">Belum ada buku di toko ini.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
@endsection
