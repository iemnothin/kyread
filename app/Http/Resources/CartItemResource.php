<?php

namespace App\Http\Resources;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CartItem */
class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'subtotal' => $this->quantity * $this->product->price,
            'available' => $this->product->is_active && $this->product->stock >= $this->quantity,
            'product' => new ProductResource($this->product),
        ];
    }
}
