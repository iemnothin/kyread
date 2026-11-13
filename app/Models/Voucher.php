<?php

namespace App\Models;

use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code', 'type', 'value', 'min_spend', 'max_discount', 'quota', 'starts_at', 'expires_at', 'is_active',
])]
class Voucher extends Model
{
    protected function casts(): array
    {
        return [
            'type' => VoucherType::class,
            'value' => 'integer',
            'min_spend' => 'integer',
            'max_discount' => 'integer',
            'quota' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Kode voucher selalu disimpan huruf besar tanpa spasi. */
    protected function code(): Attribute
    {
        return Attribute::set(fn (string $value) => static::normalize($value));
    }

    public static function normalize(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** Voucher yang sedang berlaku dan kuotanya masih ada. */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn (Builder $q) => $q->whereNull('quota')->orWhereColumn('used_count', '<', 'quota'));
    }

    /** Besar potongan untuk subtotal tertentu (tanpa memeriksa syarat). */
    public function discountFor(int $subtotal): int
    {
        $discount = match ($this->type) {
            VoucherType::Percent => intdiv($subtotal * $this->value, 100),
            VoucherType::Fixed => $this->value,
        };

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return min($discount, $subtotal);
    }

    /** Ringkasan untuk pembeli, misalnya "Diskon 10% s.d. Rp 20.000 · min. belanja Rp 100.000". */
    protected function summary(): Attribute
    {
        return Attribute::get(function () {
            $rp = fn (int $n) => 'Rp '.number_format($n, 0, ',', '.');
            $text = match (true) {
                $this->type === VoucherType::Fixed => __('Potongan :amount', ['amount' => $rp($this->value)]),
                (bool) $this->max_discount => __('Diskon :percent% s.d. :max', ['percent' => $this->value, 'max' => $rp($this->max_discount)]),
                default => __('Diskon :percent%', ['percent' => $this->value]),
            };

            return $text.($this->min_spend > 0 ? ' · '.__('min. belanja :amount', ['amount' => $rp($this->min_spend)]) : '');
        });
    }

    /** Status untuk ditampilkan, dalam bahasa aktif. status() tetap berbahasa Indonesia untuk logika. */
    public function statusLabel(): string
    {
        return match ($this->status()) {
            'Nonaktif' => __('Nonaktif'),
            'Terjadwal' => __('Terjadwal'),
            'Kedaluwarsa' => __('Kedaluwarsa'),
            'Kuota habis' => __('Kuota habis'),
            default => __('Aktif'),
        };
    }

    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'Nonaktif',
            $this->starts_at?->isFuture() => 'Terjadwal',
            $this->expires_at?->isPast() => 'Kedaluwarsa',
            $this->quota !== null && $this->used_count >= $this->quota => 'Kuota habis',
            default => 'Aktif',
        };
    }
}
