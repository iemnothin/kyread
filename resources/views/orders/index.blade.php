@extends('layouts.app')

@section('title', 'Pesanan Saya')

@section('content')
    <h1 class="h4 fw-bold mb-3">Pesanan Saya</h1>

    <ul class="nav nav-pills flex-nowrap overflow-auto mb-3 small">
        <li class="nav-item"><a class="nav-link text-nowrap {{ ! $status ? 'active bg-ky' : 'text-body' }}" href="{{ route('orders.index') }}">Semua</a></li>
        @foreach (\App\Enums\OrderStatus::cases() as $case)
            <li class="nav-item"><a class="nav-link text-nowrap {{ $status === $case ? 'active bg-ky' : 'text-body' }}" href="{{ route('orders.index', ['status' => $case->value]) }}">{{ $case->label() }}</a></li>
        @endforeach
    </ul>

    @forelse ($orders as $order)
        <div class="card card-soft mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-2 small mb-3">
                    <i class="bi bi-bag text-ky"></i>
                    <b>{{ $order->created_at->translatedFormat('d M Y') }}</b>
                    @include('partials.status-badge', ['status' => $order->status])
                    <span class="text-secondary">{{ $order->code }}</span>
                    <span class="ms-auto"><i class="bi bi-shop"></i> {{ $order->store->name }}</span>
                </div>
                @php($first = $order->items->first())
                <div class="d-flex gap-3 align-items-center">
                    <img class="thumb-sm" src="{{ $first->image_url }}" alt="">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate">{{ $first->title }}</div>
                        <small class="text-secondary">{{ $first->quantity }} × @rupiah($first->price)</small>
                        @if ($order->items->count() > 1)
                            <small class="d-block text-secondary">+{{ $order->items->count() - 1 }} buku lainnya</small>
                        @endif
                    </div>
                    <div class="text-end">
                        <small class="text-secondary d-block">Total belanja</small>
                        <b>@rupiah($order->total)</b>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a class="btn btn-outline-ky btn-sm" href="{{ route('orders.show', $order) }}">Lihat Detail</a>
                    @if ($order->status === \App\Enums\OrderStatus::Pending)
                        <a class="btn btn-ky btn-sm" href="{{ route('orders.show', $order) }}">Bayar Sekarang</a>
                    @elseif ($order->status === \App\Enums\OrderStatus::Shipped)
                        <form action="{{ route('orders.complete', $order) }}" method="POST" data-confirm="Pastikan buku sudah kamu terima. Selesaikan pesanan?">
                            @csrf
                            <button class="btn btn-ky btn-sm" type="submit">Pesanan Diterima</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card card-soft text-center py-5">
            <div class="card-body">
                <i class="bi bi-receipt display-4 text-ky"></i>
                <p class="mt-3 text-secondary">Belum ada pesanan{{ $status ? ' dengan status ini' : '' }}.</p>
                <a class="btn btn-ky" href="{{ route('books.index') }}">Belanja Sekarang</a>
            </div>
        </div>
    @endforelse

    {{ $orders->links() }}
@endsection
