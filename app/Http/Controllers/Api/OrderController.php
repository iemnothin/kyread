<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function checkout(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        $orders = $checkout->placeOrder($request->user(), $request->validated());
        $request->saveAddressIfRequested();

        return OrderResource::collection($orders->each->load('store', 'items'))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'));

        return OrderResource::collection(
            $request->user()->orders()
                ->with('store', 'items')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString()
        );
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load('store', 'items.product', 'reviews'));
    }

    public function pay(Order $order): OrderResource
    {
        return $this->transition($order, OrderStatus::Paid);
    }

    public function cancel(Order $order): OrderResource
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages(['status' => __('Pesanan yang sudah dibayar hanya bisa dibatalkan oleh penjual.')]);
        }

        return $this->transition($order, OrderStatus::Cancelled);
    }

    public function complete(Order $order): OrderResource
    {
        return $this->transition($order, OrderStatus::Completed);
    }

    public function review(Request $request, Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        if ($order->status !== OrderStatus::Completed) {
            throw ValidationException::withMessages(['status' => __('Ulasan hanya bisa diberikan untuk pesanan yang sudah selesai.')]);
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

        return $this->show($order);
    }

    private function transition(Order $order, OrderStatus $to): OrderResource
    {
        Gate::authorize('view', $order);

        $this->orders->transition($order, $to);

        return $this->show($order->refresh());
    }
}
