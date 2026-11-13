@extends('layouts.app')

@section('title', __('Checkout'))

@section('content')
    <h1 class="h4 fw-bold mb-3">{{ __('Checkout') }}</h1>

    <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form" data-subtotal="{{ $groups->sum('subtotal') }}" data-voucher-url="{{ route('checkout.voucher') }}">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                {{-- Alamat --}}
                <div class="card card-soft mb-3">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-geo-alt text-ky me-1"></i>{{ __('Alamat Pengiriman') }}</h2>
                        <div class="row g-3">
                            @foreach ([
                                ['recipient_name', __('Nama penerima'), $user->name, 'col-md-6'],
                                ['recipient_phone', 'No. HP', $user->phone, 'col-md-6'],
                                ['street', __('Alamat lengkap (jalan, no. rumah, kelurahan, kecamatan)'), $user->street, 'col-12'],
                                ['city', __('Kota/Kabupaten'), $user->city, 'col-md-5'],
                                ['province', __('Provinsi'), $user->province, 'col-md-4'],
                                ['zipcode', __('Kode pos'), $user->zipcode, 'col-md-3'],
                            ] as [$field, $label, $default, $col])
                                <div class="{{ $col }}">
                                    <label class="form-label small" for="{{ $field }}">{{ $label }}</label>
                                    <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $default) }}" required>
                                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" id="save_address" name="save_address" type="checkbox" value="1" @checked(old('save_address', ! $user->hasCompleteAddress()))>
                                    <label class="form-check-label small" for="save_address">{{ __('Simpan sebagai alamat utama di profil') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Item per toko --}}
                @foreach ($groups as $group)
                    <div class="card card-soft mb-3">
                        <div class="card-body">
                            <div class="fw-semibold mb-2"><i class="bi bi-shop text-ky me-1"></i>{{ $group['store']->name }}
                                <small class="text-secondary fw-normal">· {{ __('dikirim dari :city', ['city' => $group['store']->city]) }}</small></div>
                            @foreach ($group['items'] as $item)
                                <div class="d-flex gap-3 align-items-center py-2 border-top">
                                    <img class="thumb-sm" src="{{ $item->product->image_url }}" alt="">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="text-truncate">{{ $item->product->title }}</div>
                                        <small class="text-secondary">{{ $item->quantity }} × @rupiah($item->product->price)</small>
                                    </div>
                                    <b>@rupiah($item->quantity * $item->product->price)</b>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between small text-secondary border-top pt-2">
                                <span>{{ __('Berat total :weight kg', ['weight' => number_format($group['weight'] / 1000, 2, ',', '.')]) }}</span>
                                <span>{{ __('Ongkir:') }} <b class="text-body" data-shipping-weight="{{ $group['weight'] }}">-</b></span>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if ($groups->count() > 1)
                    <p class="small text-secondary"><i class="bi bi-info-circle"></i> {{ __('Pesanan akan dipisah per toko dan masing-masing dikenai ongkos kirim.') }}</p>
                @endif

                {{-- Kurir --}}
                <div class="card card-soft mb-3">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-truck text-ky me-1"></i>{{ __('Kurir') }}</h2>
                        <div class="row row-cols-1 row-cols-md-2 g-2">
                            @foreach ($couriers as $courier)
                                <div class="col option-card">
                                    <input id="courier-{{ $courier->value }}" name="courier" type="radio" value="{{ $courier->value }}" data-rate="{{ $courier->ratePerKg() }}" @checked(old('courier', 'jne') === $courier->value)>
                                    <label for="courier-{{ $courier->value }}">
                                        <img src="{{ asset($courier->logo()) }}" alt="">
                                        <span class="flex-grow-1">
                                            <span class="d-block fw-semibold small">{{ $courier->label() }}</span>
                                            <small class="text-secondary">{{ __(':rate/kg', ['rate' => 'Rp '.number_format($courier->ratePerKg(), 0, ',', '.')]) }} · {{ $courier->estimate() }}</small>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('courier')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Pembayaran --}}
                <div class="card card-soft mb-3">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-credit-card text-ky me-1"></i>{{ __('Metode Pembayaran') }}</h2>
                        <div class="row row-cols-1 row-cols-md-2 g-2">
                            @foreach ($payments as $payment)
                                <div class="col option-card">
                                    <input id="pay-{{ $payment->value }}" name="payment_method" type="radio" value="{{ $payment->value }}" @checked(old('payment_method', 'bca') === $payment->value)>
                                    <label for="pay-{{ $payment->value }}">
                                        <img src="{{ asset($payment->logo()) }}" alt="">
                                        <span class="small fw-semibold">{{ $payment->label() }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('payment_method')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card card-soft">
                    <div class="card-body">
                        <label class="form-label small fw-semibold" for="notes">{{ __('Catatan untuk penjual (opsional)') }}</label>
                        <input class="form-control" id="notes" name="notes" type="text" value="{{ old('notes') }}" maxlength="255" placeholder="{{ __('Contoh: tolong dibungkus kado') }}">
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-soft sticky-lg-top" style="top: 90px">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">{{ __('Ringkasan Pembayaran') }}</h2>
                        <div class="d-flex justify-content-between mb-2"><span class="text-secondary">{{ __('Total harga') }}</span><span id="sum-subtotal" data-value="{{ $groups->sum('subtotal') }}">@rupiah($groups->sum('subtotal'))</span></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-secondary">{{ __('Ongkos kirim') }}</span><span data-sum="shipping">-</span></div>
                        <div class="d-flex justify-content-between mb-2 text-success {{ old('voucher_code') ? '' : 'd-none' }}" data-voucher-row>
                            <span>{{ __('Diskon voucher') }}</span><span data-sum="discount">-</span>
                        </div>

                        <label class="form-label small fw-semibold mt-2 mb-1" for="voucher_code"><i class="bi bi-ticket-perforated text-ky"></i> {{ __('Kode voucher') }}</label>
                        <div class="input-group input-group-sm has-validation">
                            <input class="form-control text-uppercase @error('voucher_code') is-invalid @enderror" id="voucher_code" name="voucher_code" type="text" value="{{ old('voucher_code') }}" maxlength="20" placeholder="{{ __('Opsional') }}" autocomplete="off">
                            <button class="btn btn-outline-ky" type="button" data-voucher-apply>{{ __('Pakai') }}</button>
                            <div class="invalid-feedback" data-voucher-error>@error('voucher_code'){{ $message }}@enderror</div>
                        </div>
                        <div class="small text-success mt-1 d-none" data-voucher-ok></div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3"><span>{{ __('Total tagihan') }}</span><span class="text-ky" data-sum="total">-</span></div>
                        <button class="btn btn-ky w-100 py-2" type="submit">{{ __('Buat Pesanan') }}</button>
                        <p class="small text-secondary mt-2 mb-0">{{ __('Selesaikan pembayaran dalam 24 jam atau pesanan otomatis dibatalkan.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
