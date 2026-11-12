<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, CheckoutService $checkout): View
    {
        $groups = $checkout->groupedCart($request->user());

        return view('cart.index', [
            'groups' => $groups,
            'total' => $groups->sum('subtotal'),
            'count' => $groups->sum(fn ($g) => $g['items']->sum('quantity')),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1']]);
        $user = $request->user();

        if (! $product->is_active || ! $product->isInStock()) {
            return back()->with('error', 'Maaf, buku ini sedang tidak tersedia.');
        }
        if ($product->store_id === $user->store?->id) {
            return back()->with('error', 'Kamu tidak bisa membeli buku dari tokomu sendiri.');
        }

        $item = $user->cartItems()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = min($product->stock, ($item->quantity ?? 0) + ($data['quantity'] ?? 1));
        $item->save();

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.create');
        }

        return back()->with('success', "\"{$product->title}\" ditambahkan ke keranjang.");
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $item->update(['quantity' => min($data['quantity'], $item->product->stock)]);

        return back()->with(
            $data['quantity'] > $item->product->stock ? 'error' : 'success',
            $data['quantity'] > $item->product->stock
                ? "Jumlah disesuaikan dengan stok tersedia ({$item->product->stock})."
                : 'Keranjang diperbarui.'
        );
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $item->delete();

        return back()->with('success', 'Buku dihapus dari keranjang.');
    }
}
