@extends('layouts.seller')

@section('title', __('Dashboard'))

@section('content')
    <div class="row">
        @foreach ([
            [__('Pendapatan'), 'bi-cash-stack', 'Rp '.number_format($stats['revenue'], 0, ',', '.'), 'revenue-card', __('dari pesanan terbayar')],
            [__('Pesanan'), 'bi-cart-check', $stats['orders'], 'sales-card', __(':count perlu diproses', ['count' => $stats['to_process']])],
            [__('Buku Terjual'), 'bi-book', $stats['sold'], 'customers-card', trans_choice('dari :count judul', $stats['products'])],
        ] as [$label, $icon, $value, $class, $sub])
            <div class="col-md-4">
                <div class="card info-card {{ $class }}">
                    <div class="card-body">
                        <h5 class="card-title">{{ $label }}</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"><i class="bi {{ $icon }}"></i></div>
                            <div class="ps-3">
                                <h6 style="font-size: 24px">{{ $value }}</h6>
                                <span class="text-muted small">{{ $sub }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Penjualan') }} <span>{{ __('| 14 hari terakhir') }}</span></h5>
                    <div id="salesChart" data-chart='@json($chart)'></div>
                </div>
            </div>

            <div class="card recent-sales overflow-auto">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Pesanan Terbaru') }} <a class="small float-end fw-normal" href="{{ route('seller.orders.index') }}">{{ __('Lihat semua') }}</a></h5>
                    <table class="table table-borderless align-middle">
                        <thead>
                            <tr><th>{{ __('Kode') }}</th><th>{{ __('Pembeli') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ route('seller.orders.show', $order) }}">{{ $order->code }}</a></td>
                                    <td>{{ $order->user->display_name }}</td>
                                    <td>@rupiah($order->total)</td>
                                    <td>@include('partials.status-badge', ['status' => $order->status])</td>
                                </tr>
                            @empty
                                <tr><td class="text-muted" colspan="4">{{ __('Belum ada pesanan.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body pb-0">
                    <h5 class="card-title">{{ __('Buku Terlaris') }}</h5>
                    @forelse ($topProducts as $product)
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <img class="thumb-sm" src="{{ $product->image_url }}" alt="">
                            <div class="flex-grow-1 small">
                                <a class="fw-semibold text-dark d-block" href="{{ route('seller.products.edit', $product) }}">{{ \Illuminate\Support\Str::limit($product->title, 40) }}</a>
                                <span class="text-muted">{{ trans_choice(':count terjual', $product->sold_count) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">{{ __('Belum ada buku.') }} <a href="{{ route('seller.products.create') }}">{{ __('Tambah sekarang') }}</a>.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-body pb-0">
                    <h5 class="card-title">{{ __('Stok Menipis') }} <span>| ≤ 5</span></h5>
                    @forelse ($lowStock as $product)
                        <div class="d-flex justify-content-between small mb-2">
                            <a class="text-dark" href="{{ route('seller.products.edit', $product) }}">{{ \Illuminate\Support\Str::limit($product->title, 36) }}</a>
                            <span class="badge {{ $product->stock === 0 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $product->stock }}</span>
                        </div>
                    @empty
                        <p class="text-muted small">{{ __('Semua stok aman 👍') }}</p>
                    @endforelse
                    <div class="pb-3"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
