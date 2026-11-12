@extends('layouts.app')

@section('title', 'Pesanan '.$order->code)

@use('App\Enums\OrderStatus')
@php
    $steps = [
        [OrderStatus::Pending, 'bi-receipt', 'Dibuat', $order->created_at],
        [OrderStatus::Paid, 'bi-credit-card', 'Dibayar', $order->paid_at],
        [OrderStatus::Processing, 'bi-box-seam', 'Diproses', null],
        [OrderStatus::Shipped, 'bi-truck', 'Dikirim', $order->shipped_at],
        [OrderStatus::Completed, 'bi-check2-circle', 'Selesai', $order->completed_at],
    ];
    $order_rank = array_search($order->status, array_column($steps, 0), true);
    $reviewed = $order->reviews->keyBy('product_id');
@endphp

@section('content')
    <a class="small text-decoration-none" href="{{ route('orders.index') }}"><i class="bi bi-arrow-left"></i> Pesanan Saya</a>
    <div class="d-flex flex-wrap align-items-center gap-2 mt-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $order->code }}</h1>
        @include('partials.status-badge', ['status' => $order->status])
    </div>

    @if ($order->status === OrderStatus::Cancelled)
        <div class="alert alert-danger">Pesanan dibatalkan pada {{ $order->cancelled_at?->translatedFormat('d M Y H:i') }}. Stok buku sudah dikembalikan.</div>
    @else
        <div class="card card-soft mb-3">
            <div class="card-body d-flex order-timeline">
                @foreach ($steps as $i => [$status, $icon, $label, $time])
                    <div class="step {{ $order_rank !== false && $i <= $order_rank ? 'done' : '' }}">
                        <div class="dot"><i class="bi {{ $icon }}"></i></div>
                        {{ $label }}
                        @if ($time)<div class="text-secondary fw-normal" style="font-size: .7rem">{{ $time->translatedFormat('d M H:i') }}</div>@endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            @if ($order->status === OrderStatus::Pending)
                <div class="card card-soft border border-warning mb-3">
                    <div class="card-body">
                        <h2 class="h6 fw-bold">Selesaikan pembayaran</h2>
                        <p class="small mb-2">Bayar sebelum <b>{{ $order->payment_deadline->translatedFormat('d M Y H:i') }}</b> via
                            <img src="{{ asset($order->payment_method->logo()) }}" alt="" height="18"> {{ $order->payment_method->label() }}.</p>
                        <div class="bg-light rounded p-3 mb-3 small">
                            Nomor pembayaran: <b class="font-monospace">{{ '8808'.str_pad((string) $order->id, 10, '0', STR_PAD_LEFT) }}</b><br>
                            Jumlah: <b>@rupiah($order->total)</b>
                        </div>
                        <div class="d-flex gap-2">
                            <form action="{{ route('orders.pay', $order) }}" method="POST">
                                @csrf
                                <button class="btn btn-ky" type="submit"><i class="bi bi-check2 me-1"></i>Saya Sudah Bayar</button>
                            </form>
                            <form action="{{ route('orders.cancel', $order) }}" method="POST" data-confirm="Batalkan pesanan ini?">
                                @csrf
                                <button class="btn btn-outline-danger" type="submit">Batalkan</button>
                            </form>
                        </div>
                        <p class="small text-secondary mt-2 mb-0"><i class="bi bi-info-circle"></i> Mode demo: pembayaran disimulasikan, belum terhubung ke payment gateway.</p>
                    </div>
                </div>
            @elseif ($order->status === OrderStatus::Shipped)
                <div class="card card-soft mb-3">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        <div>
                            <div class="small text-secondary">Nomor resi {{ $order->courier->label() }}</div>
                            <b class="font-monospace">{{ $order->tracking_number }}</b>
                        </div>
                        <form class="ms-auto" action="{{ route('orders.complete', $order) }}" method="POST" data-confirm="Pastikan buku sudah kamu terima. Selesaikan pesanan?">
                            @csrf
                            <button class="btn btn-ky" type="submit">Pesanan Diterima</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card card-soft">
                <div class="card-body">
                    <a class="fw-semibold text-decoration-none text-body" href="{{ route('stores.show', $order->store) }}"><i class="bi bi-shop text-ky me-1"></i>{{ $order->store->name }}</a>
                    @foreach ($order->items as $item)
                        <div class="py-3 border-top mt-2">
                            <div class="d-flex gap-3 align-items-center">
                                <img class="thumb-sm" src="{{ $item->image_url }}" alt="">
                                <div class="flex-grow-1 min-w-0">
                                    @if ($item->product)
                                        <a class="text-body text-decoration-none fw-semibold" href="{{ route('books.show', $item->product) }}">{{ $item->title }}</a>
                                    @else
                                        <span class="fw-semibold">{{ $item->title }}</span>
                                    @endif
                                    <small class="d-block text-secondary">{{ $item->quantity }} × @rupiah($item->price)</small>
                                </div>
                                <b>@rupiah($item->subtotal)</b>
                            </div>

                            @if ($order->status === OrderStatus::Completed && $item->product_id)
                                @php($review = $reviewed->get($item->product_id))
                                <form class="mt-3 bg-light rounded p-3" action="{{ route('orders.review', $order) }}" method="POST">
                                    @csrf
                                    <input name="product_id" type="hidden" value="{{ $item->product_id }}">
                                    <div class="small fw-semibold mb-1">{{ $review ? 'Ulasanmu' : 'Beri ulasan' }}</div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <select class="form-select form-select-sm" name="rating" style="width: auto" aria-label="Rating">
                                            @for ($r = 5; $r >= 1; $r--)
                                                <option value="{{ $r }}" @selected(($review?->rating ?? 5) === $r)>{{ str_repeat('★', $r) }}</option>
                                            @endfor
                                        </select>
                                        <input class="form-control form-control-sm flex-grow-1" name="comment" type="text" value="{{ $review?->comment }}" maxlength="1000" placeholder="Bagaimana bukunya?" style="min-width: 200px">
                                        <button class="btn btn-ky btn-sm" type="submit">{{ $review ? 'Perbarui' : 'Kirim' }}</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-3">
                <div class="card-body small">
                    <h2 class="h6 fw-bold">Pengiriman</h2>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="{{ asset($order->courier->logo()) }}" alt="" height="20"> {{ $order->courier->label() }}
                    </div>
                    <b>{{ $order->recipient_name }}</b> · {{ $order->recipient_phone }}<br>
                    <span class="text-secondary">{{ $order->full_address }}</span>
                    @if ($order->notes)
                        <div class="mt-2"><i class="bi bi-chat-left-text"></i> {{ $order->notes }}</div>
                    @endif
                </div>
            </div>
            <div class="card card-soft">
                <div class="card-body small">
                    <h2 class="h6 fw-bold">Rincian Pembayaran</h2>
                    <div class="d-flex justify-content-between mb-1"><span class="text-secondary">Metode</span><span>{{ $order->payment_method->label() }}</span></div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-secondary">Subtotal</span><span>@rupiah($order->subtotal)</span></div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-secondary">Ongkos kirim</span><span>@rupiah($order->shipping_cost)</span></div>
                    @if ($order->discount > 0)
                        <div class="d-flex justify-content-between mb-1 text-success"><span>Voucher {{ $order->voucher_code }}</span><span>−@rupiah($order->discount)</span></div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-6"><span>Total</span><span class="text-ky">@rupiah($order->total)</span></div>
                    @if ($order->status !== OrderStatus::Cancelled)
                        <a class="btn btn-outline-ky btn-sm w-100 mt-3" href="{{ route('orders.invoice', $order) }}" target="_blank"><i class="bi bi-printer me-1"></i>Lihat / Cetak Invoice</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
