@extends('layouts.app')

@section('title', $activeCategory?->name ?? (filled($filters['q'] ?? null) ? 'Cari "'.$filters['q'].'"' : 'Katalog Buku'))

@section('content')
    <div class="row g-4">
        {{-- Filter --}}
        <aside class="col-lg-3">
            <button class="btn btn-outline-ky w-100 d-lg-none" data-bs-toggle="collapse" data-bs-target="#filterPanel" type="button">
                <i class="bi bi-funnel me-1"></i>Filter{{ $activeCategory ? ': '.$activeCategory->name : '' }}
            </button>
            <form class="card card-soft collapse d-lg-block mt-2 mt-lg-0" id="filterPanel" action="{{ route('books.index') }}" method="GET">
                <div class="card-body">
                    @if (filled($filters['q'] ?? null))
                        <input name="q" type="hidden" value="{{ $filters['q'] }}">
                    @endif

                    <h6 class="fw-bold mb-3">Kategori</h6>
                    <div class="list-group list-group-flush mb-4">
                        <a class="list-group-item list-group-item-action border-0 rounded {{ ! $activeCategory ? 'active bg-ky border-0' : '' }}"
                            href="{{ route('books.index', request()->except('category', 'page')) }}">Semua kategori</a>
                        @foreach ($categories as $category)
                            <a class="list-group-item list-group-item-action border-0 rounded {{ $activeCategory?->is($category) ? 'active bg-ky border-0' : '' }}"
                                href="{{ route('books.index', array_merge(request()->except('page'), ['category' => $category->slug])) }}">{{ $category->name }}</a>
                        @endforeach
                    </div>
                    @if ($activeCategory)
                        <input name="category" type="hidden" value="{{ $activeCategory->slug }}">
                    @endif

                    <h6 class="fw-bold mb-2">Harga</h6>
                    <div class="d-flex gap-2 mb-3">
                        <input class="form-control form-control-sm" name="min" type="number" value="{{ $filters['min'] ?? '' }}" min="0" placeholder="Min">
                        <input class="form-control form-control-sm" name="max" type="number" value="{{ $filters['max'] ?? '' }}" min="0" placeholder="Maks">
                    </div>
                    <input name="sort" type="hidden" value="{{ $filters['sort'] ?? 'newest' }}">
                    <button class="btn btn-ky btn-sm w-100" type="submit">Terapkan</button>
                    @if (array_filter($filters))
                        <a class="btn btn-link btn-sm w-100 mt-1" href="{{ route('books.index') }}">Reset filter</a>
                    @endif
                </div>
            </form>
        </aside>

        {{-- Hasil --}}
        <section class="col-lg-9">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h1 class="h4 fw-bold mb-0">
                        @if (filled($filters['q'] ?? null))
                            Hasil untuk "{{ $filters['q'] }}"
                        @else
                            {{ $activeCategory?->name ?? 'Semua Buku' }}
                        @endif
                    </h1>
                    <small class="text-secondary">{{ $products->total() }} buku ditemukan</small>
                </div>
                <form class="d-flex align-items-center gap-2" action="{{ route('books.index') }}" method="GET">
                    @foreach (request()->except('sort', 'page') as $key => $value)
                        <input name="{{ $key }}" type="hidden" value="{{ $value }}">
                    @endforeach
                    <label class="small text-secondary text-nowrap" for="sort">Urutkan</label>
                    <select class="form-select form-select-sm" id="sort" name="sort" data-autosubmit>
                        @foreach (['newest' => 'Terbaru', 'bestseller' => 'Terlaris', 'price_asc' => 'Harga terendah', 'price_desc' => 'Harga tertinggi', 'title' => 'Judul A-Z'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                @forelse ($products as $product)
                    <div class="col">@include('partials.product-card')</div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-search display-5 text-secondary"></i>
                        <p class="mt-3 text-secondary">Buku yang kamu cari belum tersedia. Coba kata kunci lain.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $products->links() }}</div>
        </section>
    </div>
@endsection
