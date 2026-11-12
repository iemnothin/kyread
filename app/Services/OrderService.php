<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function transition(Order $order, OrderStatus $to, ?string $trackingNumber = null): Order
    {
        return DB::transaction(function () use ($order, $to, $trackingNumber) {
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
    }

    /** Kembalikan stok & jumlah terjual ketika pesanan dibatalkan. */
    private function restock(Order $order): void
    {
        $order->cancelled_at = now();

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
