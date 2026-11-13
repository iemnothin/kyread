<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Bca = 'bca';
    case Bni = 'bni';
    case Bri = 'bri';
    case Mandiri = 'mandiri';
    case Mastercard = 'mastercard';
    case Paypal = 'paypal';

    public function label(): string
    {
        return match ($this) {
            self::Bca => __('BCA Virtual Account'),
            self::Bni => __('BNI Virtual Account'),
            self::Bri => __('BRI Virtual Account'),
            self::Mandiri => __('Mandiri Virtual Account'),
            self::Mastercard => __('Kartu Kredit (Mastercard)'),
            self::Paypal => __('PayPal'),
        };
    }

    public function logo(): string
    {
        return "img/payment/{$this->value}-icon.png";
    }
}
