<?php

namespace App\Enums;

enum VoucherType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => __('Persentase (%)'),
            self::Fixed => __('Potongan tetap (Rp)'),
        };
    }
}
