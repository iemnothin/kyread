<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'category_id', 'title', 'slug', 'author', 'publisher', 'isbn', 'pages', 'published_year',
    'description', 'price', 'compare_at_price', 'weight', 'stock', 'image', 'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public const string PLACEHOLDER_IMAGE = 'img/book-placeholder.svg';

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'weight' => 'integer',
            'stock' => 'integer',
            'sold_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->slug ??= static::uniqueSlug($product->title);
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'buku';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Filter katalog: pencarian, kategori, rentang harga, dan urutan.
     *
     * @param  array{q?: ?string, category?: ?string, min?: mixed, max?: mixed, promo?: mixed, sort?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, function (Builder $q, string $term): void {
                $q->where(fn (Builder $w) => $w
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('author', 'like', "%{$term}%")
                    ->orWhere('publisher', 'like', "%{$term}%"));
            })
            ->when($filters['category'] ?? null, fn (Builder $q, string $slug) => $q->whereRelation('category', 'slug', $slug))
            ->when(is_numeric($filters['min'] ?? null), fn (Builder $q) => $q->where('price', '>=', (int) $filters['min']))
            ->when(is_numeric($filters['max'] ?? null), fn (Builder $q) => $q->where('price', '<=', (int) $filters['max']))
            ->when(filter_var($filters['promo'] ?? false, FILTER_VALIDATE_BOOL), fn (Builder $q) => $q->onSale());

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'bestseller' => $query->orderByDesc('sold_count'),
            'discount' => $query->orderByRaw('CASE WHEN compare_at_price > price THEN (compare_at_price - price) * 100 / compare_at_price ELSE 0 END DESC'),
            'title' => $query->orderBy('title'),
            default => $query->latest(),
        };
    }

    /** Buku yang sedang diskon (punya harga coret). */
    #[Scope]
    protected function onSale(Builder $query): void
    {
        $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price');
    }

    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    /** Persentase diskon dibulatkan, atau 0 bila tidak sedang diskon. */
    protected function discountPercent(): Attribute
    {
        return Attribute::get(fn () => $this->compare_at_price > $this->price
            ? (int) round(($this->compare_at_price - $this->price) * 100 / $this->compare_at_price)
            : 0);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => static::imageUrlFor($this->image));
    }

    public static function imageUrlFor(?string $path): string
    {
        return match (true) {
            blank($path) => asset(self::PLACEHOLDER_IMAGE),
            str_starts_with($path, 'img/') => asset($path),
            default => asset('storage/'.$path),
        };
    }

    protected function averageRating(): Attribute
    {
        return Attribute::get(fn () => round((float) ($this->reviews_avg_rating ?? $this->reviews()->avg('rating')), 1));
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }
}
