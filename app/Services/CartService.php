<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Aturan keranjang yang dipakai bersama oleh web dan API. */
class CartService
{
    /**
     * Tambahkan buku ke keranjang (jumlah dijumlahkan dengan yang sudah ada, dibatasi stok).
     *
     * @throws ValidationException
     */
    public function add(User $user, Product $product, int $quantity = 1): CartItem
    {
        if (! $product->is_active || ! $product->isInStock()) {
            throw ValidationException::withMessages(['cart' => 'Maaf, buku ini sedang tidak tersedia.']);
        }
        if ($product->store_id === $user->store?->id) {
            throw ValidationException::withMessages(['cart' => 'Kamu tidak bisa membeli buku dari tokomu sendiri.']);
        }

        $item = $user->cartItems()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = min($product->stock, ($item->quantity ?? 0) + max(1, $quantity));
        $item->save();

        return $item->setRelation('product', $product);
    }

    /**
     * Ubah jumlah item. Mengembalikan pesan bila jumlah dipangkas karena stok.
     */
    public function update(CartItem $item, int $quantity): ?string
    {
        $stock = $item->product->stock;
        $item->update(['quantity' => max(1, min($quantity, $stock))]);

        return $quantity > $stock ? "Jumlah disesuaikan dengan stok tersedia ({$stock})." : null;
    }
}
