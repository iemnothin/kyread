<?php

namespace App\Http\Controllers;

use App\Enums\Courier;
use App\Enums\PaymentMethod;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request, CheckoutService $checkout): View|RedirectResponse
    {
        $groups = $checkout->groupedCart($request->user());

        if ($groups->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kamu masih kosong.');
        }

        return view('checkout.create', [
            'groups' => $groups,
            'user' => $request->user(),
            'couriers' => Courier::cases(),
            'payments' => PaymentMethod::cases(),
        ]);
    }

    public function store(CheckoutRequest $request, CheckoutService $checkout): RedirectResponse
    {
        $orders = $checkout->placeOrder($request->user(), $request->validated());
        $request->saveAddressIfRequested();

        $message = $orders->count() > 1
            ? "Berhasil membuat {$orders->count()} pesanan (dipisah per toko). Silakan selesaikan pembayaran."
            : 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran.';

        return $orders->count() === 1
            ? redirect()->route('orders.show', $orders->first())->with('success', $message)
            : redirect()->route('orders.index', ['status' => 'pending'])->with('success', $message);
    }
}
