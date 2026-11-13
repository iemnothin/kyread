@extends('layouts.seller')

@use('App\Enums\OrderStatus')

@section('title', __('Pesanan :code', ['code' => $order->code]))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('seller.orders.index') }}">{{ __('Pesanan') }}</a></li>
    <li class="breadcrumb-item active">{{ $order->code }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center gap-2">{{ __('Item Pesanan') }} @include('partials.status-badge', ['status' => $order->status])</h5>
                    <table class="table align-middle">
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td style="width: 60px"><img class="thumb-sm" src="{{ $item->image_url }}" alt=""></td>
                                    <td>{{ $item->title }}<br><small class="text-muted">{{ $item->quantity }} × @rupiah($item->price)</small></td>
                                    <td class="text-end fw-semibold">@rupiah($item->subtotal)</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="small">
                            <tr><td></td><td class="text-end text-muted">{{ __('Subtotal') }}</td><td class="text-end">@rupiah($order->subtotal)</td></tr>
                            <tr><td></td><td class="text-end text-muted">{{ __('Ongkir (:courier)', ['courier' => $order->courier->label()]) }}</td><td class="text-end">@rupiah($order->shipping_cost)</td></tr>
                            @if ($order->discount > 0)
                                <tr><td></td><td class="text-end text-success">{{ __('Voucher :code', ['code' => $order->voucher_code]) }}</td><td class="text-end text-success">−@rupiah($order->discount)</td></tr>
                            @endif
                            <tr><td></td><td class="text-end fw-bold">{{ __('Total') }}</td><td class="text-end fw-bold">@rupiah($order->total)</td></tr>
                        </tfoot>
                    </table>
                    @if ($order->notes)
                        <div class="alert alert-light mb-0"><i class="bi bi-chat-left-text me-1"></i> {{ __('Catatan pembeli: :notes', ['notes' => $order->notes]) }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title d-flex justify-content-between align-items-center">{{ __('Tindakan') }} <a class="btn btn-sm btn-outline-secondary" href="{{ route('orders.invoice', $order) }}" target="_blank"><i class="bi bi-printer"></i> {{ __('Invoice') }}</a></h5>
                    @switch($order->status)
                        @case(OrderStatus::Pending)
                            <p class="small text-muted">{{ __('Menunggu pembeli membayar. Pesanan otomatis batal jika tidak dibayar sampai :date.', ['date' => $order->payment_deadline->translatedFormat('d M H:i')]) }}</p>
                            @break
                        @case(OrderStatus::Paid)
                            <form class="mb-2" action="{{ route('seller.orders.update', $order) }}" method="POST">
                                @csrf @method('PATCH')
                                <input name="status" type="hidden" value="processing">
                                <button class="btn btn-ky w-100" type="submit"><i class="bi bi-box-seam me-1"></i>{{ __('Proses Pesanan') }}</button>
                            </form>
                            <form action="{{ route('seller.orders.update', $order) }}" method="POST" data-confirm="{{ __('Batalkan pesanan dan kembalikan dana ke pembeli?') }}">
                                @csrf @method('PATCH')
                                <input name="status" type="hidden" value="cancelled">
                                <button class="btn btn-outline-danger w-100" type="submit">{{ __('Tolak & Batalkan') }}</button>
                            </form>
                            @break
                        @case(OrderStatus::Processing)
                            <form action="{{ route('seller.orders.update', $order) }}" method="POST">
                                @csrf @method('PATCH')
                                <input name="status" type="hidden" value="shipped">
                                <label class="form-label small" for="tracking_number">{{ __('Nomor resi :courier', ['courier' => $order->courier->label()]) }}</label>
                                <input class="form-control mb-2 @error('tracking_number') is-invalid @enderror" id="tracking_number" name="tracking_number" type="text" value="{{ old('tracking_number') }}" required>
                                <button class="btn btn-ky w-100" type="submit"><i class="bi bi-truck me-1"></i>{{ __('Kirim Pesanan') }}</button>
                            </form>
                            @break
                        @case(OrderStatus::Shipped)
                            <p class="small mb-1">{{ __('Dikirim :date', ['date' => $order->shipped_at?->translatedFormat('d M Y H:i')]) }}</p>
                            <p class="small">{{ __('Resi:') }} <b class="font-monospace">{{ $order->tracking_number }}</b></p>
                            <p class="small text-muted mb-0">{{ __('Menunggu konfirmasi pembeli.') }}</p>
                            @break
                        @case(OrderStatus::Completed)
                            <p class="small text-success mb-0"><i class="bi bi-check-circle"></i> {{ __('Selesai :date', ['date' => $order->completed_at?->translatedFormat('d M Y H:i')]) }}</p>
                            @break
                        @case(OrderStatus::Cancelled)
                            <p class="small text-danger mb-0">{{ __('Dibatalkan :date. Stok sudah dikembalikan.', ['date' => $order->cancelled_at?->translatedFormat('d M Y H:i')]) }}</p>
                            @break
                    @endswitch
                </div>
            </div>

            <div class="card">
                <div class="card-body small">
                    <h5 class="card-title">{{ __('Pengiriman') }}</h5>
                    <b>{{ $order->recipient_name }}</b><br>
                    {{ $order->recipient_phone }}<br>
                    <span class="text-muted">{{ $order->full_address }}</span>
                    <hr>
                    <div class="d-flex justify-content-between"><span class="text-muted">{{ __('Pembayaran') }}</span><span>{{ $order->payment_method->label() }}</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">{{ __('Dibuat') }}</span><span>{{ $order->created_at->translatedFormat('d M Y H:i') }}</span></div>
                    @if ($order->paid_at)
                        <div class="d-flex justify-content-between"><span class="text-muted">{{ __('Dibayar') }}</span><span>{{ $order->paid_at->translatedFormat('d M Y H:i') }}</span></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
