<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'));

        return view('orders.index', [
            'orders' => $request->user()->orders()
                ->with('store', 'items')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('orders.show', [
            'order' => $order->load('store', 'items.product', 'reviews'),
        ]);
    }

    /** Simulasi pembayaran (belum terhubung ke payment gateway sungguhan). */
    public function pay(Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);

        $this->orders->transition($order, OrderStatus::Paid);

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi. Penjual akan segera memproses pesananmu.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);
        if ($order->status !== OrderStatus::Pending) {
            return back()->with('error', 'Pesanan yang sudah dibayar hanya bisa dibatalkan oleh penjual.');
        }

        $this->orders->transition($order, OrderStatus::Cancelled);

        return back()->with('success', 'Pesanan dibatalkan.');
    }

    public function complete(Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);

        $this->orders->transition($order, OrderStatus::Completed);

        return back()->with('success', 'Terima kasih! Pesanan selesai. Jangan lupa beri ulasan ya.');
    }

    public function review(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);
        if ($order->status !== OrderStatus::Completed) {
            return back()->with('error', 'Ulasan hanya bisa diberikan untuk pesanan yang sudah selesai.');
        }

        $data = $request->validate([
            'product_id' => ['required', Rule::in($order->items->pluck('product_id')->filter())],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->reviews()->updateOrCreate(
            ['product_id' => $data['product_id']],
            ['user_id' => $request->user()->id, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null],
        );

        return back()->with('success', 'Ulasan tersimpan. Terima kasih!');
    }
}
