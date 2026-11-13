<?php

namespace App\Http\Resources;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Voucher */
class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type->value,
            'value' => $this->value,
            'min_spend' => $this->min_spend,
            'max_discount' => $this->max_discount,
            'summary' => $this->summary,
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
