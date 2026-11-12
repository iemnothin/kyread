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
            self::Bca => 'BCA Virtual Account',
            self::Bni => 'BNI Virtual Account',
            self::Bri => 'BRI Virtual Account',
            self::Mandiri => 'Mandiri Virtual Account',
            self::Mastercard => 'Kartu Kredit (Mastercard)',
            self::Paypal => 'PayPal',
        };
    }

    public function logo(): string
    {
        return "img/payment/{$this->value}-icon.png";
    }
}
