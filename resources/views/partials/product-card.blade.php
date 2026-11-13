{{-- @include('partials.product-card', ['product' => $product]) --}}
<div class="card book-card position-relative">
    <div class="cover">
        <img src="{{ $product->image_url }}" alt="{{ __('Sampul :title', ['title' => $product->title]) }}" loading="lazy">
        @if ($product->discount_percent > 0)
            <span class="discount-badge">-{{ $product->discount_percent }}%</span>
        @endif
    </div>
    @auth
        @include('partials.wishlist-button', ['product' => $product, 'active' => isset($wishlistIds[$product->id]), 'class' => 'wish-btn-card', 'label' => null])
    @endauth
    <div class="card-body p-3 d-flex flex-column">
        <span class="badge bg-ky align-self-start mb-2">{{ $product->category->name }}</span>
        <h3 class="title mb-1">{{ $product->title }}</h3>
        <div class="meta mb-2 text-truncate">{{ $product->author }}</div>
        <div class="price mt-auto">@rupiah($product->price)</div>
        @if ($product->discount_percent > 0)
            <div class="compare-price">@rupiah($product->compare_at_price)</div>
        @endif
        <div class="meta d-flex justify-content-between mt-1">
            <span>
                @if ($product->reviews_avg_rating)
                    <i class="bi bi-star-fill rating-stars"></i> {{ number_format($product->reviews_avg_rating, 1) }} ·
                @endif
                {{ trans_choice(':count terjual', $product->sold_count) }}
            </span>
            @if ($product->stock === 0)
                <span class="text-danger">{{ __('Habis') }}</span>
            @endif
        </div>
        @if ($product->relationLoaded('store'))
            <div class="meta text-truncate mt-1"><i class="bi bi-shop"></i> {{ $product->store->city ?? $product->store->name }}</div>
        @endif
    </div>
    <a class="stretched-link" href="{{ route('books.show', $product) }}" aria-label="{{ $product->title }}"></a>
</div>
