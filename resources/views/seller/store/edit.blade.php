@extends('layouts.seller')

@section('title', 'Profil Toko')
@section('breadcrumb')<li class="breadcrumb-item active">Profil Toko</li>@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body profile-card pt-4 d-flex flex-column align-items-center text-center">
                    <img class="rounded-circle mb-2" src="{{ $store->logo_url }}" alt="" width="110" height="110" style="object-fit: cover">
                    <h2>{{ $store->name }}</h2>
                    <h3>{{ $store->city }}</h3>
                    <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('stores.show', $store) }}" target="_blank">Lihat halaman toko</a>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body pt-3">
                    @include('seller.store.fields', ['action' => route('seller.store.update'), 'method' => 'PUT', 'submit' => 'Simpan Perubahan'])
                </div>
            </div>
        </div>
    </div>
@endsection
