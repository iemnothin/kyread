<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\StoreResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {}

    /** Keranjang dikelompokkan per toko, lengkap dengan subtotal dan berat (untuk ongkir). */
    public function index(Request $request): JsonResponse
    {
        $groups = $this->checkout->groupedCart($request->user());

        return response()->json([
            'groups' => $groups->map(fn (array $g) => [
                'store' => new StoreResource($g['store']),
                'items' => CartItemResource::collection($g['items']),
                'subtotal' => $g['subtotal'],
                'weight' => $g['weight'],
            ]),
            'count' => $groups->sum(fn ($g) => $g['items']->sum('quantity')),
            'total' => $groups->sum('subtotal'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->cart->add($request->user(), Product::findOrFail($data['product_id']), (int) ($data['quantity'] ?? 1));

        return $this->index($request)->setStatusCode(201);
    }

    public function update(Request $request, CartItem $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $message = $this->cart->update($item, (int) $data['quantity']);

        return response()->json([...$this->index($request)->getData(true), 'message' => $message]);
    }

    public function destroy(Request $request, CartItem $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $item->delete();

        return $this->index($request);
    }
}
