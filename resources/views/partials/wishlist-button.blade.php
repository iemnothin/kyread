{{-- @include('partials.wishlist-button', ['product' => $product, 'active' => bool, 'class' => '...']) --}}
<form class="wish-form {{ $class ?? '' }}" action="{{ route('wishlist.toggle', $product) }}" method="POST" data-wishlist>
    @csrf
    <button class="wish-btn {{ $active ? 'active' : '' }}" type="submit" aria-pressed="{{ $active ? 'true' : 'false' }}"
        title="{{ $active ? __('Hapus dari wishlist') : __('Simpan ke wishlist') }}" aria-label="{{ __('Wishlist') }}">
        <i class="bi {{ $active ? 'bi-heart-fill' : 'bi-heart' }}"></i>
        @if (filled($label ?? null))<span class="ms-1">{{ $label }}</span>@endif
    </button>
</form>
