<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tab === 'login' ? 'Masuk' : 'Daftar' }} · {{ config('app.name') }}</title>
    <link href="{{ asset('pos/assets/img/brand.svg') }}" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,600;0,700;1,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('pos/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>

<body>
    <div class="auth-wrapper d-flex align-items-center py-5">
        <div class="container" style="max-width: 440px">
            <a class="navbar-brand ky-brand justify-content-center mb-3" href="{{ route('home') }}">
                <img src="{{ asset('pos/assets/img/brand.svg') }}" alt=""> {{ config('app.name') }}
            </a>

            <div class="card card-soft p-4">
                @include('partials.alerts')
                @if (session('loginError'))
                    <div class="alert alert-danger py-2">{{ session('loginError') }}</div>
                @endif

                <ul class="nav nav-pills nav-justified mb-4">
                    <li class="nav-item"><a class="nav-link fw-semibold {{ $tab === 'login' ? 'active bg-ky' : 'text-body' }}" href="{{ route('login') }}">Masuk</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold {{ $tab === 'register' ? 'active bg-ky' : 'text-body' }}" href="{{ route('register') }}">Daftar</a></li>
                </ul>

                @if ($tab === 'login')
                    <form action="{{ route('login') }}" method="POST" novalidate>
                        @csrf
                        <div class="form-floating mb-3">
                            <input class="form-control @error('login') is-invalid @enderror" id="login" name="login" type="text" value="{{ old('login') }}" placeholder="Email atau username" required autofocus>
                            <label for="login">Email atau username</label>
                            @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-floating mb-3">
                            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" placeholder="Password" required>
                            <label for="password">Password</label>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>
                        <button class="btn btn-ky w-100 py-2" type="submit">MASUK</button>
                    </form>
                @else
                    <form action="{{ route('register') }}" method="POST" novalidate>
                        @csrf
                        @foreach ([
                            ['name', 'text', 'Nama lengkap'],
                            ['username', 'text', 'Username'],
                            ['email', 'email', 'Email'],
                            ['password', 'password', 'Password (min. 8, huruf & angka)'],
                            ['password_confirmation', 'password', 'Ulangi password'],
                        ] as [$field, $type, $label])
                            <div class="form-floating mb-3">
                                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                                    value="{{ $type !== 'password' ? old($field) : '' }}" placeholder="{{ $label }}" required>
                                <label for="{{ $field }}">{{ $label }}</label>
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                        <button class="btn btn-ky w-100 py-2" type="submit">DAFTAR</button>
                    </form>
                @endif
            </div>
            <p class="text-center mt-3 small"><a href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> Kembali belanja</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
