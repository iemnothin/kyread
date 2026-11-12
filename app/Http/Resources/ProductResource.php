<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'author' => $this->author,
            'price' => $this->price,
            'stock' => $this->stock,
            'sold_count' => $this->sold_count,
            'weight' => $this->weight,
            'image_url' => $this->image_url,
            'rating' => $this->reviews_avg_rating !== null ? round((float) $this->reviews_avg_rating, 1) : null,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'store' => new StoreResource($this->whenLoaded('store')),
            // Hanya pada endpoint detail.
            $this->mergeWhen($request->routeIs('api.products.show'), fn () => [
                'publisher' => $this->publisher,
                'isbn' => $this->isbn,
                'pages' => $this->pages,
                'published_year' => $this->published_year,
                'description' => $this->description,
                'reviews_count' => $this->reviews_count ?? 0,
                'is_own_product' => $request->user('sanctum')?->store?->id === $this->store_id,
            ]),
        ];
    }
}
