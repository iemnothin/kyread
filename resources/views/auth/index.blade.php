<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::htmlLang() }}" dir="{{ \App\Support\Locales::dir() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tab === 'login' ? __('Masuk') : __('Daftar') }} · {{ config('app.name') }}</title>
    <meta name="robots" content="noindex">
    @include('partials.meta')
    @include('partials.i18n-script')
    @vite([\App\Support\Locales::isRtl() ? 'resources/css/app-rtl.css' : 'resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $hasError = session('loginError') || $errors->any();
    $googleIcon = '<svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>';
@endphp

<body>
    @include('partials.page-loader')

    <div class="auth-wrapper d-flex align-items-center py-4 py-lg-5" data-auth-bg>
        <div class="auth-bg" aria-hidden="true">
            <div class="auth-blob auth-blob-1"></div>
            <div class="auth-blob auth-blob-2"></div>
            <div class="auth-blob auth-blob-3"></div>
            <div class="auth-dots"></div>
            <div class="auth-floaters">
                {{-- [ikon, posisi kiri %, ukuran rem, durasi s, jeda s, putaran deg] --}}
                @foreach ([
                    ['bi-book', 6, 2.2, 22, 0, 40], ['bi-stars', 14, 1.4, 17, -6, -90], ['bi-bookmark-heart', 23, 1.8, 25, -12, 60],
                    ['bi-journal-richtext', 33, 2.6, 28, -3, -30], ['bi-heart-fill', 42, 1.2, 16, -9, 120], ['bi-ticket-perforated', 51, 1.9, 24, -15, -60],
                    ['bi-book-half', 60, 2.4, 26, -7, 45], ['bi-star-fill', 68, 1.1, 15, -2, 180], ['bi-eyeglasses', 76, 2, 23, -11, -45],
                    ['bi-journal-bookmark', 84, 2.3, 27, -18, 30], ['bi-stars', 92, 1.5, 19, -5, -120], ['bi-cup-hot', 97, 1.7, 21, -14, 70],
                    ['bi-book', 47, 1.3, 30, -20, -20], ['bi-gift', 28, 1.4, 20, -16, 90],
                ] as [$icon, $x, $size, $dur, $delay, $rot])
                    <i class="bi {{ $icon }}" style="--x: {{ $x }}%; --s: {{ $size }}rem; --d: {{ $dur }}s; --delay: {{ $delay }}s; --r: {{ $rot }}deg"></i>
                @endforeach
            </div>
        </div>

        <div class="container position-relative" style="max-width: 980px">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a class="navbar-brand ky-brand m-0" href="{{ route('home') }}">
                    <img src="{{ asset('img/brand.svg') }}" alt=""> {{ config('app.name') }}
                </a>
                @include('partials.locale-switcher')
            </div>

            <div class="auth-card card border-0 overflow-hidden {{ $hasError ? 'is-error' : '' }}" id="auth"
                data-tab="{{ $tab }}" data-check-url="{{ route('register.check') }}"
                data-title-login="{{ __('Masuk') }} · {{ config('app.name') }}" data-title-register="{{ __('Daftar') }} · {{ config('app.name') }}">
                <div class="row g-0">
                    {{-- Panel maskot --}}
                    <div class="col-lg-5 auth-hero d-flex flex-column align-items-center justify-content-center text-center p-4 p-lg-5">
                        <div class="hero-sparkles" aria-hidden="true">
                            <i class="bi bi-star-fill"></i><i class="bi bi-stars"></i><i class="bi bi-star-fill"></i><i class="bi bi-suit-heart-fill"></i>
                        </div>
                        <div class="hero-shelf" aria-hidden="true">
                            @foreach ([['#ef4444', 54], ['#f59e0b', 70], ['#22c55e', 48], ['#43aaff', 76], ['#8b5cf6', 58], ['#ec4899', 66], ['#14b8a6', 50], ['#1f8ae6', 72], ['#f97316', 56]] as $i => [$color, $h])
                                <span style="--c: {{ $color }}; --h: {{ $h }}px; --i: {{ $i }}"></span>
                            @endforeach
                        </div>
                        <div class="mascot-bubble" data-mascot-bubble
                            data-msg-login="{{ __('Hai! Senang bertemu lagi.') }}"
                            data-msg-register="{{ __('Ayo buat akunmu!') }}"
                            data-msg-password="{{ __('Tenang, aku tidak mengintip.') }}"
                            data-msg-error="{{ __('Ups, coba periksa lagi ya.') }}"
                            data-msg-name="{{ __('Salam kenal, :name!') }}">{{ $hasError ? __('Ups, coba periksa lagi ya.') : ($tab === 'login' ? __('Hai! Senang bertemu lagi.') : __('Ayo buat akunmu!')) }}</div>

                        <svg class="mascot {{ $hasError ? 'is-sad' : '' }}" data-mascot viewBox="0 0 200 200" role="img" aria-label="Kyra">
                            <ellipse class="mascot-shadow" cx="104" cy="182" rx="56" ry="8" />
                            <g class="mascot-body">
                                <rect x="48" y="40" width="16" height="122" rx="7" fill="#1f8ae6" />
                                <rect x="56" y="40" width="98" height="122" rx="14" fill="#43aaff" />
                                <rect x="146" y="50" width="8" height="102" rx="3" fill="#fff" opacity=".65" />
                                <circle cx="74" cy="114" r="7" fill="#ff8fab" opacity=".45" />
                                <circle cx="136" cy="114" r="7" fill="#ff8fab" opacity=".45" />
                                <g class="mascot-eyes">
                                    <circle cx="88" cy="90" r="15" fill="#fff" />
                                    <circle cx="124" cy="90" r="15" fill="#fff" />
                                    <g class="mascot-pupils">
                                        <circle cx="88" cy="90" r="6.5" fill="#1f2937" />
                                        <circle cx="124" cy="90" r="6.5" fill="#1f2937" />
                                        <circle cx="90.5" cy="87.5" r="2" fill="#fff" />
                                        <circle cx="126.5" cy="87.5" r="2" fill="#fff" />
                                    </g>
                                </g>
                                <path class="mascot-mouth" d="M93 120 Q106 132 119 120" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round"
                                    data-happy="M93 120 Q106 132 119 120" data-sad="M93 128 Q106 116 119 128" />
                            </g>
                            <g class="mascot-hands">
                                <rect class="mascot-hand-left" x="62" y="150" width="34" height="24" rx="12" fill="#1f8ae6" />
                                <rect class="mascot-hand-right" x="116" y="150" width="34" height="24" rx="12" fill="#1f8ae6" />
                            </g>
                        </svg>

                        <h1 class="h4 fw-bold mt-3 mb-2">{{ __('Satu akun untuk belanja dan berjualan buku.') }}</h1>
                        <ul class="auth-taglines list-unstyled mb-0" data-taglines>
                            <li class="active"><i class="bi bi-book"></i> {{ __('Ribuan judul dari berbagai toko buku') }}</li>
                            <li><i class="bi bi-ticket-perforated"></i> {{ __('Voucher dan diskon setiap hari') }}</li>
                            <li><i class="bi bi-stars"></i> {{ __('Tanya Kyra untuk rekomendasi buku') }}</li>
                        </ul>
                    </div>

                    {{-- Form --}}
                    <div class="col-lg-7 p-4 p-lg-5 bg-white">
                        @include('partials.alerts')
                        @if (session('loginError'))
                            <div class="alert alert-danger py-2 d-flex align-items-center gap-2"><i class="bi bi-exclamation-circle"></i>{{ session('loginError') }}</div>
                        @endif

                        <div class="auth-tabs mb-4" role="tablist">
                            <span class="auth-tabs-indicator" aria-hidden="true"></span>
                            <a class="{{ $tab === 'login' ? 'active' : '' }}" href="{{ route('login') }}" role="tab" data-auth-tab="login" aria-selected="{{ $tab === 'login' ? 'true' : 'false' }}">{{ __('Masuk') }}</a>
                            <a class="{{ $tab === 'register' ? 'active' : '' }}" href="{{ route('register') }}" role="tab" data-auth-tab="register" aria-selected="{{ $tab === 'register' ? 'true' : 'false' }}">{{ __('Daftar') }}</a>
                        </div>

                        {{-- Masuk --}}
                        <section class="auth-panel" data-auth-panel="login" @if ($tab !== 'login') hidden @endif>
                            @if ($googleEnabled)
                                <a class="btn btn-google w-100 py-2 d-flex align-items-center justify-content-center gap-2" href="{{ route('auth.google') }}">{!! $googleIcon !!} {{ __('Masuk dengan Google') }}</a>
                                <div class="d-flex align-items-center gap-2 my-3 text-secondary small"><hr class="flex-grow-1 my-0"> {{ __('atau') }} <hr class="flex-grow-1 my-0"></div>
                            @endif
                            <form action="{{ route('login') }}" method="POST" novalidate>
                                @csrf
                                <div class="form-floating mb-3 auth-field">
                                    <input class="form-control @error('login') is-invalid @enderror" id="login" name="login" type="text" value="{{ old('login') }}"
                                        placeholder="{{ __('Email atau username') }}" autocomplete="username" required @if ($tab === 'login') autofocus @endif data-watch>
                                    <label for="login"><i class="bi bi-person me-1"></i>{{ __('Email atau username') }}</label>
                                    @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-floating mb-1 auth-field">
                                    <input class="form-control pe-5 @error('password') is-invalid @enderror" id="password" name="password" type="password"
                                        placeholder="{{ __('Password') }}" autocomplete="current-password" required data-secret>
                                    <label for="password"><i class="bi bi-lock me-1"></i>{{ __('Password') }}</label>
                                    @include('auth.partials.password-toggle')
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="caps-hint small mb-2" data-caps hidden><i class="bi bi-capslock-fill"></i> {{ __('Caps Lock aktif') }}</div>
                                <div class="form-check form-switch my-3">
                                    <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1" role="switch" @checked(old('remember'))>
                                    <label class="form-check-label" for="remember">{{ __('Ingat saya') }}</label>
                                </div>
                                <button class="btn btn-ky w-100 py-2 fw-semibold" type="submit">{{ __('MASUK') }} <i class="bi bi-arrow-right ms-1"></i></button>
                            </form>
                        </section>

                        {{-- Daftar --}}
                        <section class="auth-panel" data-auth-panel="register" @if ($tab !== 'register') hidden @endif>
                            @if ($googleEnabled)
                                <a class="btn btn-google w-100 py-2 d-flex align-items-center justify-content-center gap-2" href="{{ route('auth.google') }}">{!! $googleIcon !!} {{ __('Daftar dengan Google') }}</a>
                                <div class="d-flex align-items-center gap-2 my-3 text-secondary small"><hr class="flex-grow-1 my-0"> {{ __('atau') }} <hr class="flex-grow-1 my-0"></div>
                            @endif
                            <form action="{{ route('register') }}" method="POST" novalidate>
                                @csrf
                                <div class="form-floating mb-3 auth-field">
                                    <input class="form-control @error('name') is-invalid @enderror" id="reg-name" name="name" type="text" value="{{ $tab === 'register' ? old('name') : '' }}"
                                        placeholder="{{ __('Nama lengkap') }}" autocomplete="name" required maxlength="60" data-watch data-greet>
                                    <label for="reg-name"><i class="bi bi-person me-1"></i>{{ __('Nama lengkap') }}</label>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-sm-6">
                                        <div class="form-floating auth-field">
                                            <input class="form-control @error('username') is-invalid @enderror" id="reg-username" name="username" type="text" value="{{ old('username') }}"
                                                placeholder="{{ __('Username') }}" autocomplete="username" required maxlength="30" data-watch data-check="username">
                                            <label for="reg-username"><i class="bi bi-at me-1"></i>{{ __('Username') }}</label>
                                            <div class="invalid-feedback" data-feedback>@error('username'){{ $message }}@enderror</div>
                                            <div class="valid-feedback" data-feedback-ok></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-floating auth-field">
                                            <input class="form-control @error('email') is-invalid @enderror" id="reg-email" name="email" type="email" value="{{ old('email') }}"
                                                placeholder="{{ __('Email') }}" autocomplete="email" required data-watch data-check="email">
                                            <label for="reg-email"><i class="bi bi-envelope me-1"></i>{{ __('Email') }}</label>
                                            <div class="invalid-feedback" data-feedback>@error('email'){{ $message }}@enderror</div>
                                            <div class="valid-feedback" data-feedback-ok></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-floating mb-2 auth-field">
                                    <input class="form-control pe-5 @error('password') is-invalid @enderror" id="reg-password" name="password" type="password"
                                        placeholder="{{ __('Password') }}" autocomplete="new-password" required data-secret data-strength-input>
                                    <label for="reg-password"><i class="bi bi-lock me-1"></i>{{ __('Password') }}</label>
                                    @include('auth.partials.password-toggle')
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="caps-hint small mb-2" data-caps hidden><i class="bi bi-capslock-fill"></i> {{ __('Caps Lock aktif') }}</div>

                                <div class="strength mb-3" data-strength data-labels='@json([__('Lemah'), __('Sedang'), __('Kuat')])'>
                                    <div class="d-flex align-items-center gap-2 small mb-1">
                                        <span class="text-secondary">{{ __('Kekuatan password') }}</span>
                                        <b class="strength-label ms-auto" data-strength-label></b>
                                    </div>
                                    <div class="strength-bars" aria-hidden="true"><span></span><span></span><span></span></div>
                                    <ul class="strength-rules list-unstyled small mb-0 mt-2">
                                        <li data-rule="length"><i class="bi bi-circle"></i> {{ __('Minimal 8 karakter') }}</li>
                                        <li data-rule="letter"><i class="bi bi-circle"></i> {{ __('Mengandung huruf') }}</li>
                                        <li data-rule="number"><i class="bi bi-circle"></i> {{ __('Mengandung angka') }}</li>
                                    </ul>
                                </div>

                                <div class="form-floating mb-4 auth-field">
                                    <input class="form-control pe-5" id="reg-password-confirmation" name="password_confirmation" type="password"
                                        placeholder="{{ __('Ulangi password') }}" autocomplete="new-password" required data-secret data-confirm-for="reg-password">
                                    <label for="reg-password-confirmation"><i class="bi bi-shield-lock me-1"></i>{{ __('Ulangi password') }}</label>
                                    @include('auth.partials.password-toggle')
                                    <div class="invalid-feedback">{{ __('Password belum cocok') }}</div>
                                    <div class="valid-feedback">{{ __('Password cocok') }}</div>
                                </div>
                                <button class="btn btn-ky w-100 py-2 fw-semibold" type="submit">{{ __('DAFTAR') }} <i class="bi bi-arrow-right ms-1"></i></button>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
            <p class="text-center mt-3 small"><a href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> {{ __('Kembali belanja') }}</a></p>
        </div>
    </div>
</body>

</html>
