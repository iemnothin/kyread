@extends('layouts.seller')

@section('title', __('Pesanan'))
@section('breadcrumb')<li class="breadcrumb-item active">{{ __('Pesanan') }}</li>@endsection

@section('content')
    <div class="card">
        <div class="card-body pt-3">
            <ul class="nav nav-tabs nav-tabs-bordered flex-nowrap overflow-auto mb-3">
                <li class="nav-item"><a class="nav-link text-nowrap {{ ! $status ? 'active' : '' }}" href="{{ route('seller.orders.index') }}">{{ __('Semua') }}</a></li>
                @foreach (\App\Enums\OrderStatus::cases() as $case)
                    <li class="nav-item">
                        <a class="nav-link text-nowrap {{ $status === $case ? 'active' : '' }}" href="{{ route('seller.orders.index', ['status' => $case->value]) }}">
                            {{ $case->label() }}
                            @if ($counts[$case->value] ?? 0)<span class="badge bg-light text-dark ms-1">{{ $counts[$case->value] }}</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <form class="d-flex gap-2 mb-3" action="{{ route('seller.orders.index') }}" method="GET" style="max-width: 360px">
                @if ($status)<input name="status" type="hidden" value="{{ $status->value }}">@endif
                <input class="form-control form-control-sm" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Cari kode pesanan') }}">
                <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>{{ __('Kode') }}</th><th>{{ __('Tanggal') }}</th><th>{{ __('Pembeli') }}</th><th>{{ __('Item') }}</th><th class="text-end">{{ __('Total') }}</th><th>{{ __('Kurir') }}</th><th>{{ __('Status') }}</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->code }}</td>
                                <td class="small text-nowrap">{{ $order->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ $order->user->display_name }}</td>
                                <td class="small">{{ trans_choice(':count buku', $order->items->sum('quantity')) }}</td>
                                <td class="text-end text-nowrap">@rupiah($order->total)</td>
                                <td class="small">{{ $order->courier->label() }}</td>
                                <td>@include('partials.status-badge', ['status' => $order->status])</td>
                                <td><a class="btn btn-sm btn-ky" href="{{ route('seller.orders.show', $order) }}">{{ __('Detail') }}</a></td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-5" colspan="8">{{ __('Belum ada pesanan.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        </div>
    </div>
@endsection
