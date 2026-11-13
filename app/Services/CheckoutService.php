<?php

namespace App\Services;

use App\Enums\Courier;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Voucher;
use App\Notifications\OrderUpdated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private readonly VoucherService $vouchers) {}

    /**
     * Cek voucher terhadap isi keranjang saat ini (untuk pratinjau di halaman checkout).
     *
     * @return array{voucher: Voucher, store_id: int, discount: int}
     */
    public function previewVoucher(User $user, string $code): array
    {
        $subtotals = $this->groupedCart($user)->mapWithKeys(fn (array $g) => [$g['store']->id => $g['subtotal']]);

        return $this->vouchers->resolve($code, $user, $subtotals);
    }

    /**
     * Ringkasan keranjang yang dikelompokkan per toko (dipakai halaman cart & checkout).
     *
     * @return Collection<int, array{store: Store, items: Collection<int, CartItem>, subtotal: int, weight: int}>
     */
    public function groupedCart(User $user): Collection
    {
        return $user->cartItems()
            ->with('product.store', 'product.category')
            ->get()
            ->groupBy('product.store_id')
            ->map(fn (Collection $items) => [
                'store' => $items->first()->product->store,
                'items' => $items,
                'subtotal' => $items->sum(fn (CartItem $i) => $i->quantity * $i->product->price),
                'weight' => $items->sum(fn (CartItem $i) => $i->quantity * $i->product->weight),
            ])
            ->values();
    }

    /**
     * Membuat satu pesanan per toko dari isi keranjang, mengurangi stok, lalu mengosongkan keranjang.
     *
     * @param  array{recipient_name: string, recipient_phone: string, street: string, city: string, province: string, zipcode: string, courier: string, payment_method: string, notes?: ?string, voucher_code?: ?string}  $data
     * @return Collection<int, Order>
     */
    public function placeOrder(User $user, array $data): Collection
    {
        $courier = Courier::from($data['courier']);
        $payment = PaymentMethod::from($data['payment_method']);

        $orders = DB::transaction(function () use ($user, $data, $courier, $payment) {
            $cart = $user->cartItems()->get();

            if ($cart->isEmpty()) {
                throw ValidationException::withMessages(['cart' => __('Keranjang belanja kamu masih kosong.')]);
            }

            // Kunci baris produk agar stok tidak balapan dengan checkout lain.
            $products = Product::whereKey($cart->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            $ownStoreId = $user->store?->id;

            foreach ($cart as $item) {
                $product = $products->get($item->product_id);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages(['cart' => __('Ada buku di keranjang yang sudah tidak dijual.')]);
                }
                if ($product->store_id === $ownStoreId) {
                    throw ValidationException::withMessages(['cart' => __('Kamu tidak bisa membeli buku dari tokomu sendiri (:title).', ['title' => $product->title])]);
                }
                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages(['cart' => __('Stok ":title" tinggal :stock.', ['title' => $product->title, 'stock' => $product->stock])]);
                }
            }

            $byStore = $cart->groupBy(fn (CartItem $item) => $products[$item->product_id]->store_id);

            // Voucher toko dikunci agar kuotanya tidak terlampaui oleh checkout bersamaan.
            $voucher = null;
            if (filled($data['voucher_code'] ?? null)) {
                $subtotals = $byStore->map(fn (Collection $items) => $items->sum(fn (CartItem $i) => $i->quantity * $products[$i->product_id]->price));
                $voucher = $this->vouchers->resolve($data['voucher_code'], $user, $subtotals, lock: true);
                $voucher['voucher']->increment('used_count');
            }

            $orders = $byStore
                ->map(function (Collection $items, int $storeId) use ($user, $data, $courier, $payment, $products, $voucher) {
                    $subtotal = $items->sum(fn (CartItem $i) => $i->quantity * $products[$i->product_id]->price);
                    $weight = $items->sum(fn (CartItem $i) => $i->quantity * $products[$i->product_id]->weight);
                    $shipping = $courier->costFor($weight);
                    $discount = $voucher && $voucher['store_id'] === $storeId ? $voucher['discount'] : 0;

                    $order = Order::create([
                        'code' => Order::generateCode(),
                        'user_id' => $user->id,
                        'store_id' => $storeId,
                        'status' => OrderStatus::Pending,
                        'recipient_name' => $data['recipient_name'],
                        'recipient_phone' => $data['recipient_phone'],
                        'street' => $data['street'],
                        'city' => $data['city'],
                        'province' => $data['province'],
                        'zipcode' => $data['zipcode'],
                        'courier' => $courier,
                        'payment_method' => $payment,
                        'subtotal' => $subtotal,
                        'shipping_cost' => $shipping,
                        'voucher_code' => $discount > 0 ? $voucher['voucher']->code : null,
                        'discount' => $discount,
                        'total' => $subtotal + $shipping - $discount,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    foreach ($items as $item) {
                        $product = $products[$item->product_id];

                        $order->items()->create([
                            'product_id' => $product->id,
                            'title' => $product->title,
                            'image' => $product->image,
                            'price' => $product->price,
                            'quantity' => $item->quantity,
                            'subtotal' => $product->price * $item->quantity,
                        ]);

                        $product->decrement('stock', $item->quantity);
                        $product->increment('sold_count', $item->quantity);
                    }

                    return $order;
                })
                ->values();

            $user->cartItems()->delete();

            return $orders;
        });

        $orders->each(fn (Order $order) => $order->store->user->notify(new OrderUpdated($order, OrderUpdated::FOR_SELLER)));

        return $orders;
    }
}
