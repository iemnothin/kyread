<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Pembeli pemilik pesanan. */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    /** Penjual pemilik toko tujuan pesanan. */
    public function manage(User $user, Order $order): bool
    {
        return $user->store?->id === $order->store_id;
    }
}
