@extends('layouts.app')

@section('title', $product->title)
@section('description', \Illuminate\Support\Str::limit($product->description, 150))

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('books.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></li>
            <li class="breadcrumb-item active text-truncate" aria-current="page">{{ $product->title }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="detail-cover">
                <img src="{{ $product->image_url }}" alt="Sampul {{ $product->title }}">
            </div>
        </div>

        <div class="col-md-7">
            @unless ($product->is_active)
                <div class="alert alert-warning py-2">Buku ini sedang dinonaktifkan dan tidak terlihat oleh pembeli.</div>
            @endunless
            <span class="badge bg-ky mb-2">{{ $product->category->name }}</span>
            <h1 class="h2 fw-bold mb-1">{{ $product->title }}</h1>
            <p class="text-secondary mb-2">oleh <a href="{{ route('books.index', ['q' => $product->author]) }}">{{ $product->author }}</a></p>
            <div class="d-flex align-items-center gap-3 small mb-3">
                @if ($product->reviews_count)
                    <span class="rating-stars"><i class="bi bi-star-fill"></i> <b class="text-body">{{ number_format($product->reviews_avg_rating, 1) }}</b></span>
                    <span class="text-secondary">{{ $product->reviews_count }} ulasan</span>
                @endif
                <span class="text-secondary">{{ $product->sold_count }} terjual</span>
            </div>
            <div class="display-6 fw-bold text-ky mb-4">@rupiah($product->price)</div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    @auth
                        @if (auth()->user()->store?->id === $product->store_id)
                            <p class="mb-2 text-secondary">Ini buku dari tokomu sendiri.</p>
                            <a class="btn btn-outline-ky" href="{{ route('seller.products.edit', $product) }}"><i class="bi bi-pencil me-1"></i>Edit di kyreadSeller</a>
                        @elseif ($product->isInStock() && $product->is_active)
                            <form action="{{ route('cart.store', $product) }}" method="POST">
                                @csrf
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="input-group qty-input" data-qty>
                                        <button class="btn btn-outline-secondary" type="button" data-step="-1" aria-label="Kurangi">−</button>
                                        <input class="form-control text-center" name="quantity" type="number" value="1" min="1" max="{{ $product->stock }}" aria-label="Jumlah">
                                        <button class="btn btn-outline-secondary" type="button" data-step="1" aria-label="Tambah">+</button>
                                    </div>
                                    <span class="small text-secondary">Stok: <b>{{ $product->stock }}</b></span>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-outline-ky flex-fill" type="submit"><i class="bi bi-bag-plus me-1"></i>Tambah ke Keranjang</button>
                                    <button class="btn btn-ky flex-fill" name="buy_now" type="submit" value="1">Beli Sekarang</button>
                                </div>
                            </form>
                        @else
                            <button class="btn btn-secondary w-100" disabled>Stok habis</button>
                        @endif
                    @else
                        <a class="btn btn-ky w-100" href="{{ route('login') }}">Masuk untuk membeli</a>
                    @endauth
                </div>
            </div>

            <a class="card card-soft flex-row align-items-center gap-3 text-decoration-none text-body p-3 mb-4" href="{{ route('stores.show', $product->store) }}">
                <img class="rounded-circle" src="{{ $product->store->logo_url }}" alt="" width="48" height="48">
                <div>
                    <div class="fw-semibold">{{ $product->store->name }}</div>
                    <small class="text-secondary"><i class="bi bi-geo-alt"></i> {{ $product->store->city }}</small>
                </div>
                <i class="bi bi-chevron-right ms-auto"></i>
            </a>

            <h2 class="h6 fw-bold">Deskripsi</h2>
            <p style="white-space: pre-line">{{ $product->description }}</p>

            <h2 class="h6 fw-bold mt-4">Detail Buku</h2>
            <table class="table table-sm small">
                <tbody>
                    <tr><th class="text-secondary fw-normal w-25">Penulis</th><td>{{ $product->author }}</td></tr>
                    @if ($product->publisher)<tr><th class="text-secondary fw-normal">Penerbit</th><td>{{ $product->publisher }}</td></tr>@endif
                    @if ($product->published_year)<tr><th class="text-secondary fw-normal">Tahun terbit</th><td>{{ $product->published_year }}</td></tr>@endif
                    @if ($product->pages)<tr><th class="text-secondary fw-normal">Jumlah halaman</th><td>{{ $product->pages }}</td></tr>@endif
                    @if ($product->isbn)<tr><th class="text-secondary fw-normal">ISBN</th><td>{{ $product->isbn }}</td></tr>@endif
                    <tr><th class="text-secondary fw-normal">Berat</th><td>{{ $product->weight }} gram</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Ulasan --}}
    <h2 class="section-title mt-5 mb-3">Ulasan Pembeli</h2>
    @forelse ($reviews as $review)
        <div class="card card-soft mb-2">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <img class="rounded-circle" src="{{ $review->user->avatar_url }}" alt="" width="28" height="28">
                    <b class="small">{{ $review->user->display_name }}</b>
                    <span class="rating-stars small">
                        @for ($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>@endfor
                    </span>
                    <small class="text-secondary ms-auto">{{ $review->created_at->diffForHumans() }}</small>
                </div>
                @if ($review->comment)<p class="mb-0 small">{{ $review->comment }}</p>@endif
            </div>
        </div>
    @empty
        <p class="text-secondary">Belum ada ulasan untuk buku ini.</p>
    @endforelse

    @if ($related->isNotEmpty())
        <h2 class="section-title mt-5 mb-3">Buku Serupa</h2>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
            @foreach ($related as $product)
                <div class="col">@include('partials.product-card')</div>
            @endforeach
        </div>
    @endif
@endsection
