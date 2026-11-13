@extends('layouts.app')

@section('title', __('Tanya Kyra'))
@section('description', __('Kyra, asisten AI kyread: minta rekomendasi buku sesuai selera dan anggaranmu.'))

@section('content')
    <div class="mx-auto" style="max-width: 820px" id="assistant" data-url="{{ route('assistant.ask') }}">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="chat-avatar"><i class="bi bi-stars"></i></div>
            <div>
                <h1 class="h4 fw-bold mb-0">{{ __('Tanya Kyra') }}</h1>
                <small class="text-secondary">{{ __('Asisten AI untuk rekomendasi buku & bantuan belanja') }}</small>
            </div>
            <button class="btn btn-link btn-sm ms-auto text-secondary" data-reset type="button"><i class="bi bi-arrow-counterclockwise"></i> {{ __('Mulai ulang') }}</button>
        </div>

        @unless ($aiEnabled)
            <div class="alert alert-warning">{{ __('Fitur asisten AI belum diaktifkan di server ini.') }}</div>
        @endunless

        <div class="card card-soft">
            <div class="card-body chat-log" data-log>
                <div class="text-center py-4" data-intro>
                    <i class="bi bi-chat-heart display-5 text-ky"></i>
                    <p class="mt-2 mb-3">{{ __('Halo! Aku Kyra. Ceritakan buku seperti apa yang kamu cari, nanti aku carikan dari katalog kyread.') }}</p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        @foreach ([
                            'Rekomendasi novel misteri yang seru',
                            'Buku bagus untuk hadiah di bawah Rp 100.000',
                            'Novel Tere Liye apa saja yang ada?',
                            'Bagaimana cara membayar pesanan?',
                        ] as $prompt)
                            <button class="btn btn-outline-ky btn-sm rounded-pill" data-prompt type="button">{{ $prompt }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <form class="card-footer bg-white border-0 d-flex gap-2 p-3">
                <textarea class="form-control" rows="1" maxlength="2000" placeholder="{{ __('Tulis pertanyaanmu…') }}" aria-label="{{ __('Pesan untuk Kyra') }}" required></textarea>
                <button class="btn btn-ky px-3" type="submit" aria-label="{{ __('Kirim') }}" @disabled(! $aiEnabled)><i class="bi bi-send"></i></button>
            </form>
        </div>
        <p class="small text-secondary text-center mt-2">{{ __('Kyra bisa keliru. Cek kembali detail buku sebelum membeli.') }}</p>
    </div>
@endsection
