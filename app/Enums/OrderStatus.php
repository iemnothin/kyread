<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Menunggu Pembayaran'),
            self::Paid => __('Dibayar'),
            self::Processing => __('Diproses Penjual'),
            self::Shipped => __('Dikirim'),
            self::Completed => __('Selesai'),
            self::Cancelled => __('Dibatalkan'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'info',
            self::Processing => 'primary',
            self::Shipped => 'secondary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Status yang boleh dituju dari status saat ini.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped],
            self::Shipped => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /** Status yang dihitung sebagai penjualan (uang sudah masuk). */
    public function countsAsRevenue(): bool
    {
        return in_array($this, [self::Paid, self::Processing, self::Shipped, self::Completed], true);
    }
}
