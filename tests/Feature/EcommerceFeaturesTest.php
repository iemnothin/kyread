<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\VoucherType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Voucher;
use App\Notifications\OrderUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EcommerceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function address(array $overrides = []): array
    {
        return array_merge([
            'recipient_name' => 'Budi', 'recipient_phone' => '081234567890', 'street' => 'Jl. Merdeka No. 1',
            'city' => 'Depok', 'province' => 'Jawa Barat', 'zipcode' => '16424',
            'courier' => 'jne', 'payment_method' => 'bca',
        ], $overrides);
    }

    private function voucher(Store $store, array $attributes = []): Voucher
    {
        return $store->vouchers()->create(array_merge([
            'code' => 'HEMAT10', 'type' => VoucherType::Percent, 'value' => 10, 'max_discount' => 15_000,
        ], $attributes));
    }

    // --- Harga coret ----------------------------------------------------------------

    public function test_discounted_products_show_strike_price_and_can_be_filtered(): void
    {
        $sale = Product::factory()->create(['title' => 'Buku Promo', 'price' => 75_000, 'compare_at_price' => 100_000]);
        Product::factory()->create(['title' => 'Buku Biasa', 'price' => 50_000]);

        $this->assertSame(25, $sale->discount_percent);

        $this->get('/')->assertOk()->assertSee('Sedang Diskon')->assertSee('-25%');
        $this->get('/books?promo=1')->assertSee('Buku Promo')->assertDontSee('Buku Biasa');
        $this->get(route('books.show', $sale))->assertSee('Rp 100.000');

        $this->getJson('/api/v1/products?promo=1')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.compare_at_price', 100_000)
            ->assertJsonPath('data.0.discount_percent', 25);
    }

    public function test_seller_compare_price_must_be_higher_than_price(): void
    {
        $product = Product::factory()->create(['price' => 50_000]);

        $this->actingAs($product->store->user)
            ->put(route('seller.products.update', $product), $product->only(
                'category_id', 'title', 'author', 'description', 'weight', 'stock'
            ) + ['price' => 50_000, 'compare_at_price' => 40_000])
            ->assertSessionHasErrors('compare_at_price');
    }

    // --- Wishlist ----------------------------------------------------------------------

    public function test_wishlist_toggle_on_web_and_api(): void
    {
        $product = Product::factory()->create(['title' => 'Buku Impian']);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('wishlist.toggle', $product))->assertJson(['wishlisted' => true]);
        $this->actingAs($user)->get(route('wishlist.index'))->assertOk()->assertSee('Buku Impian');
        // Variabel $label milik halaman (opsi urutan) tidak boleh bocor ke tombol hati di kartu buku.
        $this->actingAs($user)->get('/books')->assertOk()->assertDontSee('<span class="ms-1">Judul A-Z</span>', false);
        $this->actingAs($user)->postJson(route('wishlist.toggle', $product))->assertJson(['wishlisted' => false]);
        $this->assertSame(0, $user->wishlist()->count());

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/wishlist/{$product->slug}")->assertOk();
        $this->postJson("/api/v1/wishlist/{$product->slug}")->assertOk(); // idempoten
        $this->getJson('/api/v1/wishlist')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', $product->slug);
        $this->getJson("/api/v1/products/{$product->slug}")->assertJsonPath('data.is_wishlisted', true);
        $this->deleteJson("/api/v1/wishlist/{$product->slug}")->assertOk();
        $this->getJson('/api/v1/wishlist')->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_use_wishlist(): void
    {
        $product = Product::factory()->create();

        $this->post(route('wishlist.toggle', $product))->assertRedirect(route('login'));
        $this->postJson("/api/v1/wishlist/{$product->slug}")->assertUnauthorized();
    }

    // --- Voucher -----------------------------------------------------------------------

    public function test_voucher_discount_applies_only_to_its_store_and_counts_usage(): void
    {
        $book = Product::factory()->create(['price' => 100_000, 'weight' => 500, 'stock' => 5]);
        $other = Product::factory()->create(['price' => 40_000, 'weight' => 500, 'stock' => 5]);
        $voucher = $this->voucher($book->store, ['code' => 'hemat10', 'min_spend' => 100_000, 'quota' => 10]);
        $buyer = User::factory()->create();

        $this->assertSame('HEMAT10', $voucher->code);

        $this->actingAs($buyer)->post(route('cart.store', $book), ['quantity' => 2]);
        $this->actingAs($buyer)->post(route('cart.store', $other), ['quantity' => 1]);

        // 10% dari 200.000 = 20.000, dibatasi maks. 15.000.
        $this->actingAs($buyer)->postJson(route('checkout.voucher'), ['code' => ' hemat10 '])
            ->assertOk()
            ->assertJsonPath('discount', 15_000)
            ->assertJsonPath('store_id', $book->store_id);

        $this->actingAs($buyer)->post(route('checkout.store'), $this->address(['voucher_code' => 'HEMAT10']))->assertRedirect();

        $withVoucher = Order::where('store_id', $book->store_id)->firstOrFail();
        $withoutVoucher = Order::where('store_id', $other->store_id)->firstOrFail();

        $this->assertSame(15_000, $withVoucher->discount);
        $this->assertSame('HEMAT10', $withVoucher->voucher_code);
        $this->assertSame(200_000 + 10_000 - 15_000, $withVoucher->total);
        $this->assertSame(0, $withoutVoucher->discount);
        $this->assertSame(1, $voucher->fresh()->used_count);

        // Tidak bisa dipakai dua kali oleh pembeli yang sama.
        $this->actingAs($buyer)->post(route('cart.store', $book), ['quantity' => 1]);
        $this->actingAs($buyer)->postJson(route('checkout.voucher'), ['code' => 'HEMAT10'])
            ->assertUnprocessable()->assertJsonValidationErrors('voucher_code');

        // Pembatalan mengembalikan kuota, lalu voucher bisa dipakai lagi.
        $this->actingAs($buyer)->post(route('orders.cancel', $withVoucher));
        $this->assertSame(0, $voucher->fresh()->used_count);
    }

    public function test_voucher_rules_are_enforced(): void
    {
        $book = Product::factory()->create(['price' => 50_000]);
        $store = $book->store;
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.store', $book), ['quantity' => 1]);

        $check = fn (string $code) => $this->actingAs($buyer)->postJson(route('checkout.voucher'), ['code' => $code]);

        $this->voucher($store, ['code' => 'MINBELI', 'min_spend' => 100_000]);
        $check('MINBELI')->assertUnprocessable()->assertJsonFragment(['Minimal belanja di '.$store->name.' Rp 100.000 untuk memakai voucher ini.']);

        $this->voucher($store, ['code' => 'BASI', 'expires_at' => now()->subDay()]);
        $check('BASI')->assertUnprocessable();

        $this->voucher($store, ['code' => 'HABIS', 'quota' => 1])->forceFill(['used_count' => 1])->save();
        $check('HABIS')->assertUnprocessable();

        $otherStore = Store::factory()->create();
        $this->voucher($otherStore, ['code' => 'TOKOLAIN']);
        $check('TOKOLAIN')->assertUnprocessable()
            ->assertJsonPath('errors.voucher_code.0', "Voucher TOKOLAIN hanya berlaku untuk buku dari toko {$otherStore->name}.");

        $check('NGAWUR')->assertUnprocessable();

        // Checkout dengan voucher tidak valid gagal dan keranjang tetap utuh.
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address(['voucher_code' => 'BASI']))->assertSessionHasErrors('voucher_code');
        $this->assertSame(1, $buyer->cartItems()->count());
        $this->assertSame(0, Order::count());
    }

    public function test_seller_manages_vouchers(): void
    {
        $store = Store::factory()->create();
        $seller = $store->user;

        $this->actingAs($seller)->post(route('seller.vouchers.store'), [
            'code' => 'promo 25', 'type' => 'percent', 'value' => 95,
        ])->assertSessionHasErrors('value');

        $this->actingAs($seller)->post(route('seller.vouchers.store'), [
            'code' => 'promo 25', 'type' => 'percent', 'value' => 25, 'max_discount' => 30_000,
            'min_spend' => 50_000, 'is_active' => 1,
        ])->assertRedirect(route('seller.vouchers.index'));

        $voucher = Voucher::firstOrFail();
        $this->assertSame('PROMO25', $voucher->code);
        $this->actingAs($seller)->get(route('seller.vouchers.index'))->assertOk()->assertSee('PROMO25')->assertSee('Aktif');

        // Penjual lain tidak boleh mengubahnya.
        $intruder = Store::factory()->create()->user;
        $this->actingAs($intruder)->get(route('seller.vouchers.edit', $voucher))->assertForbidden();

        $this->actingAs($seller)->delete(route('seller.vouchers.destroy', $voucher))->assertSessionHas('success');
        $this->assertSame(0, Voucher::count());
    }

    // --- Notifikasi -----------------------------------------------------------------------

    public function test_order_events_notify_buyer_and_seller(): void
    {
        $book = Product::factory()->create(['price' => 50_000]);
        $seller = $book->store->user;
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post(route('cart.store', $book));
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address());
        $order = Order::firstOrFail();

        $this->assertSame('Pesanan baru masuk', $seller->notifications()->first()->data['title']);

        $this->actingAs($buyer)->post(route('orders.pay', $order));
        $this->actingAs($seller)->patch(route('seller.orders.update', $order), ['status' => 'processing']);
        $this->actingAs($seller)->patch(route('seller.orders.update', $order), ['status' => 'shipped', 'tracking_number' => 'JNE777']);

        $this->assertSame(2, $seller->notifications()->count()); // baru + dibayar
        $this->assertSame(2, $buyer->notifications()->count()); // diproses + dikirim
        $this->assertStringContainsString('JNE777', $buyer->notifications->firstWhere('data.title', 'Pesanan dikirim')->data['body']);

        // Halaman web menampilkan lalu menandai dibaca; tautan membuka pesanan.
        $this->actingAs($buyer)->get('/')->assertSee('bi-bell');
        $this->actingAs($buyer)->get(route('notifications.index'))->assertOk()->assertSee('Pesanan dikirim');
        $this->assertSame(0, $buyer->unreadNotifications()->count());
        $this->actingAs($buyer)->get(route('notifications.open', $buyer->notifications()->first()->id))
            ->assertRedirect(route('orders.show', $order));
        $this->actingAs($seller)->get(route('notifications.open', $seller->notifications()->first()->id))
            ->assertRedirect(route('seller.orders.show', $order));

        // API
        Sanctum::actingAs($seller);
        // Satu sudah dibuka lewat tautan di atas, sisa satu yang belum dibaca.
        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('unread_count', 1)->assertJsonCount(2, 'data');
        $this->postJson('/api/v1/notifications/read')->assertOk();
        $this->getJson('/api/v1/me')->assertJsonPath('data.unread_notifications', 0);
    }

    public function test_auto_expired_order_notifies_both_parties(): void
    {
        $book = Product::factory()->create();
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.store', $book));
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address());
        Order::query()->update(['created_at' => now()->subDays(2)]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, Order::first()->status);
        $this->assertSame('Pesanan dibatalkan', $buyer->notifications()->first()->data['title']);
        $this->assertTrue($book->store->user->notifications->contains(fn ($n) => $n->data['title'] === 'Pesanan dibatalkan'));
        $this->assertSame(OrderUpdated::FOR_SELLER, $book->store->user->notifications()->first()->data['audience']);
    }

    // --- Invoice ----------------------------------------------------------------------------

    public function test_invoice_access_rules(): void
    {
        $book = Product::factory()->create(['title' => 'Bukunya']);
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.store', $book));
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address());
        $order = Order::firstOrFail();
        auth()->logout();

        $this->get(route('orders.invoice', $order))->assertUnauthorized();
        $this->actingAs(User::factory()->create())->get(route('orders.invoice', $order))->assertForbidden();
        $this->actingAs($buyer)->get(route('orders.invoice', $order))->assertOk()->assertSee('INVOICE')->assertSee('Bukunya');
        $this->actingAs($book->store->user)->get(route('orders.invoice', $order))->assertOk();
        auth()->logout();

        // Tautan bertanda tangan dari API bisa dibuka tanpa login.
        Sanctum::actingAs($buyer);
        $url = $this->getJson("/api/v1/orders/{$order->code}")->json('data.invoice_url');
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertOk()->assertSee($order->code);
        $this->get(URL::temporarySignedRoute('orders.invoice', now()->subMinute(), ['order' => $order->code]))->assertUnauthorized();
    }
}
