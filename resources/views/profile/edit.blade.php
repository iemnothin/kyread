@extends('layouts.app')

@section('title', __('Edit Profil'))

@section('content')
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card card-soft text-center">
                <div class="card-body py-4">
                    <img class="avatar-lg mb-3" id="avatarPreview" src="{{ $user->avatar_url }}" alt="">
                    <h1 class="h5 fw-bold mb-0">{{ $user->name }}</h1>
                    <small class="text-secondary d-block">{{ '@'.$user->username }}</small>
                    <small class="text-secondary">{{ $user->email }}</small>
                    <p class="small mt-3 mb-0">{{ $user->about ?: __('Belum ada bio singkat.') }}</p>
                </div>
            </div>
            <div class="list-group mt-3 small">
                <a class="list-group-item list-group-item-action" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-2"></i>{{ __('Pesanan Saya') }}</a>
                <a class="list-group-item list-group-item-action" href="{{ route('seller.dashboard') }}"><i class="bi bi-shop me-2"></i>{{ $user->store ? __('Toko Saya') : __('Buka Toko') }}</a>
            </div>
        </div>

        <div class="col-lg-9">
            <form class="card card-soft mb-4" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="card-body">
                    <h2 class="h6 fw-bold text-ky mb-3">{{ __('Data Diri') }}</h2>
                    <div class="row g-3">
                        @foreach ([
                            ['name', __('Nama lengkap'), 'text'], ['nickname', __('Nama panggilan'), 'text'],
                            ['username', __('Username'), 'text'], ['email', __('Email'), 'email'],
                            ['phone', 'No. HP', 'text'], ['about', __('Bio singkat'), 'text'],
                        ] as [$field, $label, $type])
                            <div class="col-md-6">
                                <label class="form-label small" for="{{ $field }}">{{ $label }}</label>
                                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field, $user->$field) }}">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                        <div class="col-md-6">
                            <label class="form-label small" for="avatar">{{ __('Foto profil (maks. 1 MB)') }}</label>
                            <input class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar" type="file" data-preview="#avatarPreview" accept="image/*">
                            @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <h2 class="h6 fw-bold text-ky mt-4 mb-3">{{ __('Alamat Utama') }}</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small" for="street">{{ __('Alamat lengkap') }} <small class="text-secondary">{{ __('(jalan/gang, no. rumah, kelurahan, kecamatan)') }}</small></label>
                            <input class="form-control @error('street') is-invalid @enderror" id="street" name="street" type="text" value="{{ old('street', $user->street) }}">
                            @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @foreach ([['city', __('Kota/Kabupaten'), 'col-md-5'], ['province', __('Provinsi'), 'col-md-4'], ['zipcode', __('Kode pos'), 'col-md-3']] as [$field, $label, $col])
                            <div class="{{ $col }}">
                                <label class="form-label small" for="{{ $field }}">{{ $label }}</label>
                                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $user->$field) }}">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="text-end mt-4">
                        <button class="btn btn-ky px-4" type="submit">{{ __('Simpan Perubahan') }}</button>
                    </div>
                </div>
            </form>

            <form class="card card-soft mb-4" action="{{ route('profile.password') }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <h2 class="h6 fw-bold text-ky mb-3">{{ $user->password ? __('Ganti Password') : __('Buat Password') }}</h2>
                    @unless ($user->password)<p class="small text-secondary">{{ __('Kamu masuk dengan Google. Buat password agar bisa masuk juga dengan email/username.') }}</p>@endunless
                    <div class="row g-3">
                        @foreach (array_filter([$user->password ? ['current_password', __('Password saat ini')] : null, ['password', __('Password baru')], ['password_confirmation', __('Ulangi password baru')]]) as [$field, $label])
                            <div class="col-md-4">
                                <label class="form-label small" for="pw-{{ $field }}">{{ $label }}</label>
                                <input class="form-control @error($field, 'password') is-invalid @enderror" id="pw-{{ $field }}" name="{{ $field }}" type="password">
                                @error($field, 'password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="text-end mt-3"><button class="btn btn-outline-ky" type="submit">{{ $user->password ? __('Ganti Password') : __('Buat Password') }}</button></div>
                </div>
            </form>

            <form class="card card-soft border border-danger-subtle" action="{{ route('profile.destroy') }}" method="POST" data-confirm="{{ __('Akun dan seluruh datamu (termasuk toko) akan dihapus permanen. Lanjutkan?') }}">
                @csrf @method('DELETE')
                <div class="card-body">
                    <h2 class="h6 fw-bold text-danger">{{ __('Hapus Akun') }}</h2>
                    <p class="small text-secondary">{{ __('Tindakan ini permanen. Masukkan password untuk konfirmasi.') }}</p>
                    <div class="d-flex gap-2" style="max-width: 420px">
                        @if ($user->password)<input class="form-control @error('password', 'deletion') is-invalid @enderror" name="password" type="password" placeholder="{{ __('Password') }}" aria-label="{{ __('Password') }}">@endif
                        <button class="btn btn-danger text-nowrap" type="submit">{{ __('Hapus Akun') }}</button>
                    </div>
                    @error('password', 'deletion')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </form>
        </div>
    </div>
@endsection
