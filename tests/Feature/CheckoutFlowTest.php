<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private function address(array $overrides = []): array
    {
        return array_merge([
            'recipient_name' => 'Budi',
            'recipient_phone' => '081234567890',
            'street' => 'Jl. Merdeka No. 1',
            'city' => 'Depok',
            'province' => 'Jawa Barat',
            'zipcode' => '16424',
            'courier' => 'jne',
            'payment_method' => 'bca',
        ], $overrides);
    }

    public function test_full_order_lifecycle_from_cart_to_review(): void
    {
        $product = Product::factory()->create(['price' => 50_000, 'weight' => 1_200, 'stock' => 5]);
        $buyer = User::factory()->create();
        $seller = $product->store->user;

        // Tambah ke keranjang (2x dijumlahkan).
        $this->actingAs($buyer)->post(route('cart.store', $product), ['quantity' => 1])->assertSessionHas('success');
        $this->actingAs($buyer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->assertSame(2, $buyer->cartItems()->first()->quantity);

        // Checkout: 2,4 kg -> dibulatkan 3 kg × Rp10.000 (JNE).
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address())->assertRedirect();
        $order = Order::firstOrFail();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(100_000, $order->subtotal);
        $this->assertSame(30_000, $order->shipping_cost);
        $this->assertSame(130_000, $order->total);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(0, $buyer->cartItems()->count());

        // Penjual belum boleh mengirim pesanan yang belum dibayar.
        $this->actingAs($seller)->patch(route('seller.orders.update', $order), ['status' => 'shipped', 'tracking_number' => 'X'])
            ->assertSessionHasErrors('status');

        $this->actingAs($buyer)->post(route('orders.pay', $order));
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);

        $this->actingAs($seller)->patch(route('seller.orders.update', $order), ['status' => 'processing']);
        $this->actingAs($seller)->patch(route('seller.orders.update', $order), ['status' => 'shipped'])
            ->assertSessionHasErrors('tracking_number');
        $this->actingAs($seller)->patch(route('seller.orders.update', $order), ['status' => 'shipped', 'tracking_number' => 'JNE123']);
        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);

        $this->actingAs($buyer)->post(route('orders.complete', $order));
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);

        $this->actingAs($buyer)->post(route('orders.review', $order), [
            'product_id' => $product->id, 'rating' => 5, 'comment' => 'Mantap',
        ])->assertSessionHas('success');
        $this->assertSame(5, (int) $product->reviews()->avg('rating'));

        // Halaman detail & dashboard penjual tetap bisa dirender.
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->assertSee('Ulasanmu');
        $this->actingAs($seller)->get(route('seller.dashboard'))->assertOk()->assertSee('Rp 130.000');
        $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk();
    }

    public function test_cart_items_from_two_stores_create_two_orders(): void
    {
        [$a, $b] = Product::factory()->count(2)->create();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post(route('cart.store', $a));
        $this->actingAs($buyer)->post(route('cart.store', $b));
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address(['courier' => 'sicepat']))
            ->assertRedirect(route('orders.index', ['status' => 'pending']));

        $this->assertSame(2, Order::count());
        $this->assertEqualsCanonicalizing([$a->store_id, $b->store_id], Order::pluck('store_id')->all());
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create(['stock' => 3]);
        $buyer = User::factory()->create();
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);
        $product->update(['stock' => 1]); // stok berkurang setelah masuk keranjang

        $this->actingAs($buyer)->post(route('checkout.store'), $this->address())->assertSessionHasErrors('cart');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_cancelling_pending_order_restores_stock(): void
    {
        $product = Product::factory()->create(['stock' => 4]);
        $buyer = User::factory()->create();
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 3]);
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address());
        $order = Order::firstOrFail();
        $this->assertSame(1, $product->fresh()->stock);

        $this->actingAs($buyer)->post(route('orders.cancel', $order));

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertSame(0, $product->fresh()->sold_count);
    }

    public function test_unpaid_orders_expire_after_24_hours(): void
    {
        $product = Product::factory()->create(['stock' => 2]);
        $buyer = User::factory()->create();
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address());

        $this->travel(25)->hours();
        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, Order::first()->status);
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_seller_cannot_buy_own_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($product->store->user)->post(route('cart.store', $product))->assertSessionHas('error');
        $this->assertSame(0, $product->store->user->cartItems()->count());
    }

    public function test_seller_cannot_manage_other_stores_order(): void
    {
        $product = Product::factory()->create();
        $buyer = User::factory()->create();
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address());

        $otherSeller = Store::factory()->create()->user;

        $this->actingAs($otherSeller)->get(route('seller.orders.show', Order::first()))->assertForbidden();
    }
}
