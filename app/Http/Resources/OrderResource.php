<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Http\Controllers\InvoiceController;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $date = fn ($value) => $value?->toIso8601String();

        return [
            'code' => $this->code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'store' => new StoreResource($this->whenLoaded('store')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_slug' => $item->product?->slug,
                'title' => $item->title,
                'image_url' => $item->image_url,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
            ])),
            'items_count' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'recipient' => [
                'name' => $this->recipient_name,
                'phone' => $this->recipient_phone,
                'address' => $this->full_address,
            ],
            'courier' => ['code' => $this->courier->value, 'label' => $this->courier->label()],
            'payment_method' => ['code' => $this->payment_method->value, 'label' => $this->payment_method->label()],
            'tracking_number' => $this->tracking_number,
            'notes' => $this->notes,
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'voucher_code' => $this->voucher_code,
            'discount' => $this->discount,
            'total' => $this->total,
            'payment_deadline' => $this->status === OrderStatus::Pending ? $date($this->payment_deadline) : null,
            'virtual_account' => $this->status === OrderStatus::Pending ? '8808'.str_pad((string) $this->id, 10, '0', STR_PAD_LEFT) : null,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'invoice_url' => $this->status !== OrderStatus::Cancelled ? InvoiceController::signedUrl($this->resource) : null,
            'can' => [
                'pay' => $this->status === OrderStatus::Pending,
                'cancel' => $this->status === OrderStatus::Pending,
                'complete' => $this->status === OrderStatus::Shipped,
                'review' => $this->status === OrderStatus::Completed,
            ],
            'created_at' => $date($this->created_at),
            'paid_at' => $date($this->paid_at),
            'shipped_at' => $date($this->shipped_at),
            'completed_at' => $date($this->completed_at),
            'cancelled_at' => $date($this->cancelled_at),
        ];
    }
}
