<?php

namespace App\Http\Controllers;

use App\Enums\Courier;
use App\Enums\PaymentMethod;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:60'],
            'recipient_phone' => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'zipcode' => ['required', 'digits:5'],
            'courier' => ['required', Rule::enum(Courier::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:255'],
            'save_address' => ['nullable', 'boolean'],
        ], [
            'recipient_phone.regex' => 'Format nomor HP tidak valid (contoh: 081234567890).',
        ]);

        $user = $request->user();
        $orders = $checkout->placeOrder($user, $data);

        if ($request->boolean('save_address')) {
            $user->update([
                'phone' => $data['recipient_phone'],
                'street' => $data['street'],
                'city' => $data['city'],
                'province' => $data['province'],
                'zipcode' => $data['zipcode'],
            ]);
        }

        $message = $orders->count() > 1
            ? "Berhasil membuat {$orders->count()} pesanan (dipisah per toko). Silakan selesaikan pembayaran."
            : 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran.';

        return $orders->count() === 1
            ? redirect()->route('orders.show', $orders->first())->with('success', $message)
            : redirect()->route('orders.index', ['status' => 'pending'])->with('success', $message);
    }
}
