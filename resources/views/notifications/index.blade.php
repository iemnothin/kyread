@extends('layouts.app')

@section('title', __('Notifikasi'))

@section('content')
    <div class="d-flex align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-bell text-ky"></i> {{ __('Notifikasi') }}</h1>
    </div>

    <div class="card card-soft">
        <div class="list-group list-group-flush rounded">
            @forelse ($notifications as $notification)
                @php($fresh = $unreadIds->contains($notification->id))
                <a class="list-group-item list-group-item-action notif-item d-flex gap-3 py-3 {{ $fresh ? 'unread' : '' }}" href="{{ route('notifications.open', $notification->id) }}">
                    <i class="bi {{ ($notification->data['audience'] ?? '') === 'seller' ? 'bi-shop' : 'bi-bag' }} fs-4 text-ky"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2">
                            <b>{{ \App\Notifications\OrderUpdated::titleFor($notification->data) }}</b>
                            @if (($notification->data['audience'] ?? '') === 'seller')
                                <span class="badge text-bg-light border">kyreadSeller</span>
                            @endif
                            @if ($fresh)<span class="dot ms-1" title="{{ __('Baru') }}"></span>@endif
                        </div>
                        <div class="small">{{ \App\Notifications\OrderUpdated::bodyFor($notification->data) }}</div>
                        <small class="text-secondary">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <i class="bi bi-chevron-right align-self-center text-secondary"></i>
                </a>
            @empty
                <div class="text-center py-5">
                    @include('partials.lottie', ['name' => 'empty', 'size' => 150, 'icon' => 'bi-bell-slash display-5 text-secondary', 'class' => 'mx-auto'])
                    <p class="mt-3 mb-0 text-secondary">{{ __('Belum ada notifikasi. Kabar tentang pesananmu akan muncul di sini.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
@endsection
