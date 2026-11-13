@extends('layouts.app')

@section('title', __('Keranjang'))

@section('content')
    <h1 class="h4 fw-bold mb-3">{{ __('Keranjang Belanja') }}</h1>

    @if ($groups->isEmpty())
        <div class="card card-soft text-center py-5">
            <div class="card-body">
                <i class="bi bi-bag display-4 text-ky"></i>
                <h2 class="h5 mt-3">{{ __('Keranjangmu masih kosong') }}</h2>
                <p class="text-secondary">{{ __('Yuk, cari buku favoritmu sekarang.') }}</p>
                <a class="btn btn-ky px-4" href="{{ route('books.index') }}">{{ __('Mulai Belanja') }}</a>
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                @foreach ($groups as $group)
                    <div class="card card-soft mb-3">
                        <div class="card-header bg-white border-0 pt-3">
                            <a class="fw-semibold text-decoration-none text-body" href="{{ route('stores.show', $group['store']) }}">
                                <i class="bi bi-shop text-ky me-1"></i>{{ $group['store']->name }}
                            </a>
                            <small class="text-secondary ms-2">{{ $group['store']->city }}</small>
                        </div>
                        <ul class="list-group list-group-flush">
                            @foreach ($group['items'] as $item)
                                @php($product = $item->product)
                                <li class="list-group-item py-3">
                                    <div class="d-flex gap-3">
                                        <a href="{{ route('books.show', $product) }}"><img class="thumb-sm" src="{{ $product->image_url }}" alt=""></a>
                                        <div class="flex-grow-1 min-w-0">
                                            <a class="fw-semibold text-body text-decoration-none d-block text-truncate" href="{{ route('books.show', $product) }}">{{ $product->title }}</a>
                                            <small class="text-secondary d-block">{{ $product->author }}</small>
                                            <div class="fw-bold text-ky">@rupiah($product->price)</div>
                                            @if (! $product->is_active || $product->stock === 0)
                                                <small class="text-danger">{{ __('Tidak tersedia, hapus untuk melanjutkan.') }}</small>
                                            @elseif ($item->quantity > $product->stock)
                                                <small class="text-danger">{{ __('Stok tinggal :stock.', ['stock' => $product->stock]) }}</small>
                                            @endif
                                        </div>
                                        <div class="d-flex flex-column align-items-end gap-2">
                                            <form class="d-flex" action="{{ route('cart.update', $item) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <input class="form-control form-control-sm text-center" name="quantity" type="number" value="{{ $item->quantity }}"
                                                    min="1" max="{{ max(1, $product->stock) }}" style="width: 72px" data-autosubmit aria-label="{{ __('Jumlah') }}">
                                            </form>
                                            <form action="{{ route('cart.destroy', $item) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-sm text-danger p-0" type="submit"><i class="bi bi-trash"></i> {{ __('Hapus') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <div class="col-lg-4">
                <div class="card card-soft sticky-lg-top" style="top: 90px">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">{{ __('Ringkasan Belanja') }}</h2>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">{{ trans_choice('Total harga (:count buku)', $count) }}</span>
                            <span>@rupiah($total)</span>
                        </div>
                        <p class="small text-secondary">{{ __('Ongkos kirim dihitung saat checkout.') }}</p>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                            <span>{{ __('Subtotal') }}</span><span class="text-ky">@rupiah($total)</span>
                        </div>
                        <a class="btn btn-ky w-100 py-2" href="{{ route('checkout.create') }}">{{ __('Checkout') }}</a>
                        <a class="btn btn-link w-100 mt-1" href="{{ route('books.index') }}">{{ __('Lanjut belanja') }}</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
