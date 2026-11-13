<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    /**
     * Cari voucher dan pastikan bisa dipakai untuk isi keranjang.
     *
     * @param  Collection<int, int>  $subtotals  subtotal keranjang per store_id
     * @return array{voucher: Voucher, store_id: int, discount: int}
     *
     * @throws ValidationException
     */
    public function resolve(string $code, User $user, Collection $subtotals, bool $lock = false): array
    {
        $voucher = Voucher::with('store')
            ->where('code', Voucher::normalize($code))
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();

        $fail = fn (string $message) => throw ValidationException::withMessages(['voucher_code' => $message]);

        if (! $voucher || $voucher->status() !== 'Aktif') {
            $fail(__('Kode voucher tidak ditemukan atau sudah tidak berlaku.'));
        }

        $subtotal = $subtotals->get($voucher->store_id);
        if ($subtotal === null) {
            $fail(__('Voucher :code hanya berlaku untuk buku dari toko :store.', ['code' => $voucher->code, 'store' => $voucher->store->name]));
        }

        if ($subtotal < $voucher->min_spend) {
            $fail(__('Minimal belanja di :store :amount untuk memakai voucher ini.', ['store' => $voucher->store->name, 'amount' => 'Rp '.number_format($voucher->min_spend, 0, ',', '.')]));
        }

        $alreadyUsed = Order::where('user_id', $user->id)
            ->where('voucher_code', $voucher->code)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->exists();
        if ($alreadyUsed) {
            $fail(__('Kamu sudah pernah memakai voucher ini.'));
        }

        return ['voucher' => $voucher, 'store_id' => $voucher->store_id, 'discount' => $voucher->discountFor($subtotal)];
    }

    /** Kembalikan kuota voucher ketika pesanan yang memakainya dibatalkan. */
    public function release(Order $order): void
    {
        if ($order->voucher_code) {
            Voucher::where('code', $order->voucher_code)->where('used_count', '>', 0)->decrement('used_count');
        }
    }
}
