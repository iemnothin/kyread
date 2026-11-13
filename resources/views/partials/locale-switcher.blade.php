{{-- Pemilih bahasa. @include('partials.locale-switcher', ['class' => '...', 'compact' => bool]) --}}
@use('App\Support\Locales')
<div class="dropdown locale-switcher {{ $class ?? '' }}">
    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-1" data-bs-toggle="dropdown" type="button"
        aria-expanded="false" aria-label="{{ __('Bahasa') }}" title="{{ __('Bahasa') }}">
        <i class="bi bi-translate"></i>
        <span class="{{ ($compact ?? false) ? 'text-uppercase' : '' }}">{{ ($compact ?? false) ? app()->getLocale() : Locales::current()['native'] }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
        @foreach (Locales::all() as $code => $locale)
            <li>
                <a class="dropdown-item d-flex justify-content-between gap-3 {{ app()->getLocale() === $code ? 'active' : '' }}"
                    href="{{ route('locale.switch', $code) }}" lang="{{ $locale['html'] }}" dir="{{ $locale['dir'] }}">
                    <span>{{ $locale['native'] }}</span>
                    <small class="text-uppercase opacity-50">{{ $code }}</small>
                </a>
            </li>
        @endforeach
    </ul>
</div>
