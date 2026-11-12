@extends('layouts.seller')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        @foreach ([
            ['Pendapatan', 'bi-cash-stack', 'Rp '.number_format($stats['revenue'], 0, ',', '.'), 'revenue-card', 'dari pesanan terbayar'],
            ['Pesanan', 'bi-cart-check', $stats['orders'], 'sales-card', $stats['to_process'].' perlu diproses'],
            ['Buku Terjual', 'bi-book', $stats['sold'], 'customers-card', 'dari '.$stats['products'].' judul'],
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
                    <h5 class="card-title">Penjualan <span>| 14 hari terakhir</span></h5>
                    <div id="salesChart"></div>
                </div>
            </div>

            <div class="card recent-sales overflow-auto">
                <div class="card-body">
                    <h5 class="card-title">Pesanan Terbaru <a class="small float-end fw-normal" href="{{ route('seller.orders.index') }}">Lihat semua</a></h5>
                    <table class="table table-borderless align-middle">
                        <thead>
                            <tr><th>Kode</th><th>Pembeli</th><th>Total</th><th>Status</th></tr>
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
                                <tr><td class="text-muted" colspan="4">Belum ada pesanan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body pb-0">
                    <h5 class="card-title">Buku Terlaris</h5>
                    @forelse ($topProducts as $product)
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <img class="thumb-sm" src="{{ $product->image_url }}" alt="">
                            <div class="flex-grow-1 small">
                                <a class="fw-semibold text-dark d-block" href="{{ route('seller.products.edit', $product) }}">{{ \Illuminate\Support\Str::limit($product->title, 40) }}</a>
                                <span class="text-muted">{{ $product->sold_count }} terjual</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">Belum ada buku. <a href="{{ route('seller.products.create') }}">Tambah sekarang</a>.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-body pb-0">
                    <h5 class="card-title">Stok Menipis <span>| ≤ 5</span></h5>
                    @forelse ($lowStock as $product)
                        <div class="d-flex justify-content-between small mb-2">
                            <a class="text-dark" href="{{ route('seller.products.edit', $product) }}">{{ \Illuminate\Support\Str::limit($product->title, 36) }}</a>
                            <span class="badge {{ $product->stock === 0 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $product->stock }}</span>
                        </div>
                    @empty
                        <p class="text-muted small">Semua stok aman 👍</p>
                    @endforelse
                    <div class="pb-3"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const data = @json($chart);
            new ApexCharts(document.querySelector('#salesChart'), {
                chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Nunito, sans-serif' },
                series: [{ name: 'Penjualan', data: data.map(d => d.total) }],
                xaxis: { categories: data.map(d => d.date) },
                yaxis: { labels: { formatter: v => 'Rp ' + Math.round(v / 1000) + 'rb' } },
                tooltip: { y: { formatter: v => 'Rp ' + v.toLocaleString('id-ID') } },
                colors: ['#43aaff'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: .35, opacityTo: .05 } },
            }).render();
        });
    </script>
@endpush
