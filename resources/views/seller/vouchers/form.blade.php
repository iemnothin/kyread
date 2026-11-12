@extends('layouts.seller')

@use('App\Enums\VoucherType')
@php($editing = $voucher->exists)

@section('title', $editing ? 'Edit Voucher' : 'Buat Voucher')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('seller.vouchers.index') }}">Voucher</a></li>
    <li class="breadcrumb-item active">{{ $editing ? 'Edit' : 'Buat' }}</li>
@endsection

@php($dt = fn ($value) => $value?->format('Y-m-d\TH:i'))

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form class="card" action="{{ $editing ? route('seller.vouchers.update', $voucher) : route('seller.vouchers.store') }}" method="POST">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="card-body">
                    <h5 class="card-title">Detail Voucher</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="code">Kode voucher <span class="text-danger">*</span></label>
                            <input class="form-control text-uppercase @error('code') is-invalid @enderror" id="code" name="code" type="text" value="{{ old('code', $voucher->code) }}" maxlength="20" placeholder="Contoh: HEMAT10" required>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">4–20 huruf/angka, unik di seluruh kyread.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="type">Jenis potongan <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                @foreach (VoucherType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected(old('type', $voucher->type?->value) === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="value">Nilai potongan <span class="text-danger">*</span></label>
                            <input class="form-control @error('value') is-invalid @enderror" id="value" name="value" type="number" min="1" value="{{ old('value', $voucher->value) }}" required>
                            @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Persen (maks. 90) atau rupiah.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="max_discount">Maks. potongan (Rp)</label>
                            <input class="form-control @error('max_discount') is-invalid @enderror" id="max_discount" name="max_discount" type="number" min="1000" value="{{ old('max_discount', $voucher->max_discount) }}" placeholder="Tanpa batas">
                            @error('max_discount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Hanya untuk jenis persentase.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="min_spend">Min. belanja (Rp)</label>
                            <input class="form-control @error('min_spend') is-invalid @enderror" id="min_spend" name="min_spend" type="number" min="0" value="{{ old('min_spend', $voucher->min_spend) }}">
                            @error('min_spend')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="quota">Kuota pemakaian</label>
                            <input class="form-control @error('quota') is-invalid @enderror" id="quota" name="quota" type="number" min="1" value="{{ old('quota', $voucher->quota) }}" placeholder="Tanpa batas">
                            @error('quota')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="starts_at">Mulai berlaku</label>
                            <input class="form-control @error('starts_at') is-invalid @enderror" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $dt($voucher->starts_at)) }}">
                            @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="expires_at">Berakhir</label>
                            <input class="form-control @error('expires_at') is-invalid @enderror" id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at', $dt($voucher->expires_at)) }}">
                            @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $voucher->is_active))>
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2 justify-content-end">
                    <a class="btn btn-light" href="{{ route('seller.vouchers.index') }}">Batal</a>
                    <button class="btn btn-ky" type="submit">{{ $editing ? 'Simpan Perubahan' : 'Buat Voucher' }}</button>
                </div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body small">
                    <h5 class="card-title">Aturan voucher</h5>
                    <ul class="ps-3 mb-0">
                        <li>Potongan hanya untuk subtotal buku dari tokomu, tidak termasuk ongkir.</li>
                        <li>Setiap pembeli hanya bisa memakai satu voucher yang sama sekali.</li>
                        <li>Jika pesanan dibatalkan, kuota voucher dikembalikan.</li>
                        @if ($editing)
                            <li>Sudah dipakai <b>{{ $voucher->used_count }}</b> kali.</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
