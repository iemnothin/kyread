@extends('layouts.seller')

@section('title', __('Daftar Buku'))
@section('breadcrumb')<li class="breadcrumb-item active">{{ __('Daftar Buku') }}</li>@endsection

@section('content')
    <div class="card">
        <div class="card-body pt-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <form class="d-flex flex-wrap gap-2" action="{{ route('seller.products.index') }}" method="GET">
                    <input class="form-control form-control-sm" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Judul / penulis') }}" style="width: 200px">
                    <select class="form-select form-select-sm" name="category" style="width: auto" data-autosubmit>
                        <option value="">{{ __('Semua kategori') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm" name="sort" style="width: auto" data-autosubmit>
                        @foreach (['newest' => __('Terbaru'), 'bestseller' => __('Terlaris'), 'title' => __('Judul A-Z'), 'price_asc' => __('Harga ↑'), 'price_desc' => __('Harga ↓')] as $v => $l)
                            <option value="{{ $v }}" @selected(request('sort', 'newest') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </form>
                <a class="btn btn-ky btn-sm ms-auto" href="{{ route('seller.products.create') }}"><i class="bi bi-plus-lg me-1"></i>{{ __('Tambah Buku') }}</a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Buku') }}</th>
                            <th>{{ __('Kategori') }}</th>
                            <th class="text-end">{{ __('Harga') }}</th>
                            <th class="text-center">{{ __('Stok') }}</th>
                            <th class="text-center">{{ __('Terjual') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img class="thumb-sm" src="{{ $product->image_url }}" alt="">
                                        <div>
                                            <div class="fw-semibold">{{ $product->title }}</div>
                                            <small class="text-muted">{{ $product->author }} · {{ $product->weight }} gr</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product->category->name }}</td>
                                <td class="text-end text-nowrap">@rupiah($product->price)</td>
                                <td class="text-center"><span class="badge {{ $product->stock === 0 ? 'bg-danger' : ($product->stock <= 5 ? 'bg-warning text-dark' : 'bg-light text-dark') }}">{{ $product->stock }}</span></td>
                                <td class="text-center">{{ $product->sold_count }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $product->is_active ? __('Aktif') : __('Nonaktif') }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-info" href="{{ route('books.show', $product) }}" target="_blank" title="{{ __('Lihat di toko') }}"><i class="bi bi-eye"></i></a>
                                    <a class="btn btn-sm btn-outline-warning" href="{{ route('seller.products.edit', $product) }}" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></a>
                                    <form class="d-inline" action="{{ route('seller.products.destroy', $product) }}" method="POST" data-confirm="{{ __('Hapus ":title" dari toko?', ['title' => $product->title]) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('Hapus') }}"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted py-5" colspan="7">
                                    {{ __('Belum ada buku.') }} <a href="{{ route('seller.products.create') }}">{{ __('Tambah buku pertamamu') }}</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        </div>
    </div>
@endsection
