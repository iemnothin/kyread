@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card card-soft text-center">
                <div class="card-body py-4">
                    <img class="avatar-lg mb-3" src="{{ $user->avatar_url }}" alt="">
                    <h1 class="h5 fw-bold mb-0">{{ $user->name }}</h1>
                    <small class="text-secondary d-block">{{ '@'.$user->username }}</small>
                    <small class="text-secondary">{{ $user->email }}</small>
                    <p class="small mt-3 mb-0">{{ $user->about ?: 'Belum ada bio singkat.' }}</p>
                </div>
            </div>
            <div class="list-group mt-3 small">
                <a class="list-group-item list-group-item-action" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-2"></i>Pesanan Saya</a>
                <a class="list-group-item list-group-item-action" href="{{ route('seller.dashboard') }}"><i class="bi bi-shop me-2"></i>{{ $user->store ? 'Toko Saya' : 'Buka Toko' }}</a>
            </div>
        </div>

        <div class="col-lg-9">
            <form class="card card-soft mb-4" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="card-body">
                    <h2 class="h6 fw-bold text-ky mb-3">Data Diri</h2>
                    <div class="row g-3">
                        @foreach ([
                            ['name', 'Nama lengkap', 'text'], ['nickname', 'Nama panggilan', 'text'],
                            ['username', 'Username', 'text'], ['email', 'Email', 'email'],
                            ['phone', 'No. HP', 'text'], ['about', 'Bio singkat', 'text'],
                        ] as [$field, $label, $type])
                            <div class="col-md-6">
                                <label class="form-label small" for="{{ $field }}">{{ $label }}</label>
                                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field, $user->$field) }}">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                        <div class="col-md-6">
                            <label class="form-label small" for="avatar">Foto profil (maks. 1 MB)</label>
                            <input class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar" type="file" accept="image/*">
                            @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <h2 class="h6 fw-bold text-ky mt-4 mb-3">Alamat Utama</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small" for="street">Alamat lengkap <small class="text-secondary">(jalan/gang, no. rumah, kelurahan, kecamatan)</small></label>
                            <input class="form-control @error('street') is-invalid @enderror" id="street" name="street" type="text" value="{{ old('street', $user->street) }}">
                            @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @foreach ([['city', 'Kota/Kabupaten', 'col-md-5'], ['province', 'Provinsi', 'col-md-4'], ['zipcode', 'Kode pos', 'col-md-3']] as [$field, $label, $col])
                            <div class="{{ $col }}">
                                <label class="form-label small" for="{{ $field }}">{{ $label }}</label>
                                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $user->$field) }}">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="text-end mt-4">
                        <button class="btn btn-ky px-4" type="submit">Simpan Perubahan</button>
                    </div>
                </div>
            </form>

            <form class="card card-soft mb-4" action="{{ route('profile.password') }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <h2 class="h6 fw-bold text-ky mb-3">Ganti Password</h2>
                    <div class="row g-3">
                        @foreach ([['current_password', 'Password saat ini'], ['password', 'Password baru'], ['password_confirmation', 'Ulangi password baru']] as [$field, $label])
                            <div class="col-md-4">
                                <label class="form-label small" for="pw-{{ $field }}">{{ $label }}</label>
                                <input class="form-control @error($field, 'password') is-invalid @enderror" id="pw-{{ $field }}" name="{{ $field }}" type="password">
                                @error($field, 'password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="text-end mt-3"><button class="btn btn-outline-ky" type="submit">Ganti Password</button></div>
                </div>
            </form>

            <form class="card card-soft border border-danger-subtle" action="{{ route('profile.destroy') }}" method="POST" data-confirm="Akun dan seluruh datamu (termasuk toko) akan dihapus permanen. Lanjutkan?">
                @csrf @method('DELETE')
                <div class="card-body">
                    <h2 class="h6 fw-bold text-danger">Hapus Akun</h2>
                    <p class="small text-secondary">Tindakan ini permanen. Masukkan password untuk konfirmasi.</p>
                    <div class="d-flex gap-2" style="max-width: 420px">
                        <input class="form-control @error('password', 'deletion') is-invalid @enderror" name="password" type="password" placeholder="Password" aria-label="Password">
                        <button class="btn btn-danger text-nowrap" type="submit">Hapus Akun</button>
                    </div>
                    @error('password', 'deletion')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </form>
        </div>
    </div>
@endsection
