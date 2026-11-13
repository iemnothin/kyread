@extends('layouts.seller')

@section('title', __('Buka Toko'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Mulai jualan buku di kyread') }}</h5>
                    <p class="text-muted small">{{ __('Gratis dan cuma butuh 1 menit. Kamu tetap bisa berbelanja dengan akun yang sama.') }}</p>
                    @include('seller.store.fields', ['store' => new \App\Models\Store, 'action' => route('seller.store.store'), 'method' => 'POST', 'submit' => __('Buka Toko')])
                </div>
            </div>
        </div>
    </div>
@endsection
