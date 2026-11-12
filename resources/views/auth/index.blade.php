<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tab === 'login' ? 'Masuk' : 'Daftar' }} · {{ config('app.name') }}</title>
    <meta name="robots" content="noindex">
    @include('partials.meta')
    @vite('resources/js/app.js')
</head>

<body>
    <div class="auth-wrapper d-flex align-items-center py-5">
        <div class="container" style="max-width: 440px">
            <a class="navbar-brand ky-brand justify-content-center mb-3" href="{{ route('home') }}">
                <img src="{{ asset('img/brand.svg') }}" alt=""> {{ config('app.name') }}
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

                @if ($googleEnabled)
                    <a class="btn btn-google w-100 py-2 d-flex align-items-center justify-content-center gap-2" href="{{ route('auth.google') }}">
                        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/>
                            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                            <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
                            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/>
                        </svg>
                        {{ $tab === 'login' ? 'Masuk' : 'Daftar' }} dengan Google
                    </a>
                    <div class="d-flex align-items-center gap-2 my-3 text-secondary small">
                        <hr class="flex-grow-1 my-0"> atau <hr class="flex-grow-1 my-0">
                    </div>
                @endif

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
</body>

</html>
