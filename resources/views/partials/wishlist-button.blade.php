{{-- @include('partials.wishlist-button', ['product' => $product, 'active' => bool, 'class' => '...']) --}}
<form class="wish-form {{ $class ?? '' }}" action="{{ route('wishlist.toggle', $product) }}" method="POST" data-wishlist>
    @csrf
    <button class="wish-btn {{ $active ? 'active' : '' }}" type="submit" aria-pressed="{{ $active ? 'true' : 'false' }}"
        title="{{ $active ? 'Hapus dari wishlist' : 'Simpan ke wishlist' }}" aria-label="Wishlist">
        <i class="bi {{ $active ? 'bi-heart-fill' : 'bi-heart' }}"></i>
        @isset($label)<span class="ms-1">{{ $label }}</span>@endisset
    </button>
</form>
