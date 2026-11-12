<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly VoucherService $vouchers) {}

    public function transition(Order $order, OrderStatus $to, ?string $trackingNumber = null): Order
    {
        $order = DB::transaction(function () use ($order, $to, $trackingNumber) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->status->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => "Pesanan berstatus \"{$order->status->label()}\" tidak bisa diubah menjadi \"{$to->label()}\".",
                ]);
            }

            if ($to === OrderStatus::Shipped && blank($trackingNumber)) {
                throw ValidationException::withMessages(['tracking_number' => 'Nomor resi wajib diisi saat mengirim pesanan.']);
            }

            $order->status = $to;

            match ($to) {
                OrderStatus::Paid => $order->paid_at = now(),
                OrderStatus::Shipped => [$order->shipped_at = now(), $order->tracking_number = $trackingNumber],
                OrderStatus::Completed => $order->completed_at = now(),
                OrderStatus::Cancelled => $this->restock($order),
                default => null,
            };

            $order->save();

            return $order;
        });

        $this->notify($order, $to);

        return $order;
    }

    /** Beri tahu pembeli dan/atau penjual sesuai perubahan status. */
    private function notify(Order $order, OrderStatus $status): void
    {
        $order->loadMissing('user', 'store.user');

        foreach ([OrderUpdated::FOR_BUYER => $order->user, OrderUpdated::FOR_SELLER => $order->store->user] as $audience => $recipient) {
            if ($recipient && OrderUpdated::shouldNotify($status, $audience)) {
                $recipient->notify(new OrderUpdated($order, $audience, $status));
            }
        }
    }

    /** Kembalikan stok & jumlah terjual ketika pesanan dibatalkan. */
    private function restock(Order $order): void
    {
        $order->cancelled_at = now();
        $this->vouchers->release($order);

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            Product::whereKey($item->product_id)
                ->where('sold_count', '>=', $item->quantity)
                ->decrement('sold_count', $item->quantity);
        }
    }

    /** Batalkan otomatis pesanan yang belum dibayar melewati batas waktu. */
    public function expireUnpaid(): int
    {
        $count = 0;

        Order::where('status', OrderStatus::Pending)
            ->where('created_at', '<', now()->subDay())
            ->each(function (Order $order) use (&$count): void {
                $this->transition($order, OrderStatus::Cancelled);
                $count++;
            });

        return $count;
    }
}
