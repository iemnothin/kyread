<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi in-app (disimpan di tabel notifications) untuk pembeli atau penjual
 * setiap kali pesanan dibuat atau berubah status.
 */
class OrderUpdated extends Notification
{
    public const string FOR_BUYER = 'buyer';

    public const string FOR_SELLER = 'seller';

    public function __construct(
        public readonly Order $order,
        public readonly string $audience,
        public readonly ?OrderStatus $status = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** Pasangan pesan per status; null berarti pihak ini tidak perlu diberi tahu. */
    public static function shouldNotify(OrderStatus $status, string $audience): bool
    {
        return self::message($status, $audience, 'X', null) !== null;
    }

    /** @return array{0: string, 1: string}|null */
    private static function message(?OrderStatus $status, string $audience, string $code, ?string $tracking): ?array
    {
        return match ([$status, $audience]) {
            [null, self::FOR_SELLER] => ['Pesanan baru masuk', "Pesanan {$code} menunggu pembayaran pembeli."],
            [OrderStatus::Paid, self::FOR_SELLER] => ['Pesanan sudah dibayar', "Pesanan {$code} sudah dibayar. Segera proses dan kirim."],
            [OrderStatus::Processing, self::FOR_BUYER] => ['Pesanan diproses', "Penjual sedang menyiapkan pesanan {$code}."],
            [OrderStatus::Shipped, self::FOR_BUYER] => ['Pesanan dikirim', "Pesanan {$code} sudah dikirim dengan resi {$tracking}."],
            [OrderStatus::Completed, self::FOR_SELLER] => ['Pesanan selesai', "Pembeli sudah menerima pesanan {$code}."],
            [OrderStatus::Cancelled, self::FOR_BUYER], [OrderStatus::Cancelled, self::FOR_SELLER] => ['Pesanan dibatalkan', "Pesanan {$code} dibatalkan."],
            default => null,
        };
    }

    public function toArray(object $notifiable): array
    {
        [$title, $body] = self::message($this->status, $this->audience, $this->order->code, $this->order->tracking_number);

        return [
            'title' => $title,
            'body' => $body,
            'order_code' => $this->order->code,
            'audience' => $this->audience,
            'status' => $this->status?->value ?? OrderStatus::Pending->value,
        ];
    }
}
