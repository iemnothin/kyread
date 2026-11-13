<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Locales;
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
        return self::templates($status, $audience) !== null;
    }

    /**
     * Template judul & isi; diterjemahkan saat ditampilkan sesuai bahasa pembaca.
     *
     * @return array{0: string, 1: string}|null
     */
    private static function templates(?OrderStatus $status, string $audience): ?array
    {
        return match ([$status, $audience]) {
            [null, self::FOR_SELLER] => [Locales::key('Pesanan baru masuk'), Locales::key('Pesanan :code menunggu pembayaran pembeli.')],
            [OrderStatus::Paid, self::FOR_SELLER] => [Locales::key('Pesanan sudah dibayar'), Locales::key('Pesanan :code sudah dibayar. Segera proses dan kirim.')],
            [OrderStatus::Processing, self::FOR_BUYER] => [Locales::key('Pesanan diproses'), Locales::key('Penjual sedang menyiapkan pesanan :code.')],
            [OrderStatus::Shipped, self::FOR_BUYER] => [Locales::key('Pesanan dikirim'), Locales::key('Pesanan :code sudah dikirim dengan resi :tracking.')],
            [OrderStatus::Completed, self::FOR_SELLER] => [Locales::key('Pesanan selesai'), Locales::key('Pembeli sudah menerima pesanan :code.')],
            [OrderStatus::Cancelled, self::FOR_BUYER], [OrderStatus::Cancelled, self::FOR_SELLER] => [Locales::key('Pesanan dibatalkan'), Locales::key('Pesanan :code dibatalkan.')],
            default => null,
        };
    }

    /** Judul dalam bahasa aktif. */
    public static function titleFor(array $data): string
    {
        return __($data['title'] ?? 'Notifikasi');
    }

    /** Isi dalam bahasa aktif (notifikasi lama tanpa template ditampilkan apa adanya). */
    public static function bodyFor(array $data): string
    {
        return isset($data['body_key']) ? __($data['body_key'], $data['params'] ?? []) : ($data['body'] ?? '');
    }

    public function toArray(object $notifiable): array
    {
        [$title, $body] = self::templates($this->status, $this->audience);
        $params = ['code' => $this->order->code, 'tracking' => (string) $this->order->tracking_number];

        return [
            'title' => $title,
            'body_key' => $body,
            'params' => $params,
            'body' => __($body, $params, 'id'),
            'order_code' => $this->order->code,
            'audience' => $this->audience,
            'status' => $this->status?->value ?? OrderStatus::Pending->value,
        ];
    }
}
