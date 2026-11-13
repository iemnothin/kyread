<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'));
        $store = $request->user()->store;

        return view('seller.orders.index', [
            'orders' => $store->orders()
                ->with('user', 'items')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($request->query('q'), fn ($q, $term) => $q->where('code', 'like', "%{$term}%"))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
            'counts' => $store->orders()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('manage', $order);

        return view('seller.orders.show', ['order' => $order->load('user', 'items.product')]);
    }

    public function update(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        Gate::authorize('manage', $order);

        // Penjual hanya boleh memproses, mengirim, atau membatalkan pesanan yang sudah dibayar.
        $data = $request->validate([
            'status' => ['required', Rule::in(['processing', 'shipped', 'cancelled'])],
            'tracking_number' => ['nullable', 'string', 'max:40'],
        ]);

        $to = OrderStatus::from($data['status']);
        if ($to === OrderStatus::Cancelled && $order->status === OrderStatus::Pending) {
            return back()->with('error', __('Pesanan yang belum dibayar akan dibatalkan otomatis setelah 24 jam.'));
        }

        $service->transition($order, $to, $data['tracking_number'] ?? null);

        return back()->with('success', __('Status pesanan :code diubah menjadi ":status".', ['code' => $order->code, 'status' => $to->label()]));
    }
}
