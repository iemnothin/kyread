<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_endpoints(): void
    {
        $product = Product::factory()->create(['title' => 'Laskar Pelangi', 'author' => 'Andrea Hirata']);
        Product::factory()->create(['is_active' => false, 'title' => 'Tersembunyi']);

        $this->getJson('/api/v1/meta')->assertOk()->assertJsonCount(6, 'couriers')->assertJsonCount(6, 'payment_methods');
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonStructure(['data' => [['id', 'name', 'slug', 'icon_url', 'products_count']]]);

        $this->getJson('/api/v1/products?q=andrea')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laskar Pelangi')
            ->assertJsonStructure(['data' => [['id', 'slug', 'price', 'image_url', 'category', 'store']], 'meta' => ['current_page', 'last_page', 'total']]);

        $this->getJson("/api/v1/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.description', $product->description)
            ->assertJsonStructure(['data', 'reviews', 'related']);

        $this->getJson('/api/v1/products/tersembunyi')->assertNotFound();
    }

    public function test_register_login_me_logout(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sari', 'username' => 'sari', 'email' => 'sari@example.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email', 'address']]);

        $this->postJson('/api/v1/auth/login', ['login' => 'sari', 'password' => 'salah'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.login.0', 'Email/username atau password salah.');

        $token = $this->postJson('/api/v1/auth/login', ['login' => 'sari@example.com', 'password' => 'rahasia123'])
            ->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.username', 'sari');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_protected_endpoints_require_token(): void
    {
        $this->getJson('/api/v1/cart')->assertUnauthorized();
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }

    public function test_cart_checkout_pay_and_review_via_api(): void
    {
        $product = Product::factory()->create(['price' => 40_000, 'weight' => 500, 'stock' => 3]);
        Sanctum::actingAs($buyer = User::factory()->create());

        $item = $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 5])
            ->assertCreated()
            ->assertJsonPath('count', 3) // dibatasi stok
            ->assertJsonPath('total', 120_000)
            ->json('groups.0.items.0');

        $this->patchJson("/api/v1/cart/{$item['id']}", ['quantity' => 2])->assertOk()->assertJsonPath('count', 2);

        $code = $this->postJson('/api/v1/checkout', [
            'recipient_name' => 'Budi', 'recipient_phone' => '081234567890', 'street' => 'Jl. Merdeka 1',
            'city' => 'Depok', 'province' => 'Jawa Barat', 'zipcode' => '16424',
            'courier' => 'jnt', 'payment_method' => 'bri', 'save_address' => true,
        ])->assertCreated()
            ->assertJsonPath('data.0.total', 80_000 + 9_000)
            ->assertJsonPath('data.0.can.pay', true)
            ->json('data.0.code');

        $this->assertSame('Depok', $buyer->fresh()->city);
        $this->getJson('/api/v1/cart')->assertJsonPath('count', 0);

        $this->postJson("/api/v1/orders/{$code}/pay")->assertOk()->assertJsonPath('data.status', 'paid');
        $this->postJson("/api/v1/orders/{$code}/cancel")->assertUnprocessable();

        // Simulasikan penjual memproses & mengirim.
        Order::where('code', $code)->update(['status' => 'shipped', 'tracking_number' => 'X1']);

        $this->postJson("/api/v1/orders/{$code}/complete")->assertOk()->assertJsonPath('data.can.review', true);
        $this->postJson("/api/v1/orders/{$code}/review", ['product_id' => $product->id, 'rating' => 4, 'comment' => 'Oke'])
            ->assertOk()->assertJsonPath('data.reviews.0.rating', 4);

        $this->getJson('/api/v1/orders?status=completed')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_cannot_access_other_users_cart_item_or_order(): void
    {
        $product = Product::factory()->create();
        $owner = User::factory()->create();
        $item = $owner->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson("/api/v1/cart/{$item->id}")->assertForbidden();
    }

    public function test_validation_messages_are_in_indonesian(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/checkout', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.recipient_name.0', 'Nama penerima wajib diisi.');
    }
}
