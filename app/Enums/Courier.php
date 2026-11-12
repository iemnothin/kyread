<?php

namespace App\Enums;

enum Courier: string
{
    case Jne = 'jne';
    case Jnt = 'jnt';
    case SiCepat = 'sicepat';
    case Tiki = 'tiki';
    case GoSend = 'gosend';
    case GrabExpress = 'grabexpress';

    public function label(): string
    {
        return match ($this) {
            self::Jne => 'JNE Reguler',
            self::Jnt => 'J&T Express',
            self::SiCepat => 'SiCepat REG',
            self::Tiki => 'TIKI Reguler',
            self::GoSend => 'GoSend Same Day',
            self::GrabExpress => 'GrabExpress Same Day',
        };
    }

    public function logo(): string
    {
        return "img/logistics/{$this->value}-logo.png";
    }

    /** Tarif per kilogram (dibulatkan ke atas). */
    public function ratePerKg(): int
    {
        return match ($this) {
            self::Jne => 10_000,
            self::Jnt => 9_000,
            self::SiCepat => 8_500,
            self::Tiki => 9_500,
            self::GoSend => 20_000,
            self::GrabExpress => 19_000,
        };
    }

    public function estimate(): string
    {
        return match ($this) {
            self::GoSend, self::GrabExpress => 'Hari ini',
            self::SiCepat, self::Jnt => '2-3 hari',
            self::Jne, self::Tiki => '2-4 hari',
        };
    }

    public function costFor(int $weightInGrams): int
    {
        $kg = max(1, (int) ceil($weightInGrams / 1000));

        return $kg * $this->ratePerKg();
    }
}
