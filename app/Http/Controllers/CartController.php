<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

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

        try {
            $this->cart->add($request->user(), $product, (int) ($data['quantity'] ?? 1));
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.create');
        }

        return back()->with('success', "\"{$product->title}\" ditambahkan ke keranjang.");
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $adjusted = $this->cart->update($item, (int) $data['quantity']);

        return back()->with($adjusted ? 'error' : 'success', $adjusted ?? __('Keranjang diperbarui.'));
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $item->delete();

        return back()->with('success', __('Buku dihapus dari keranjang.'));
    }
}
