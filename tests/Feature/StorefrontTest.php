<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_pages_render(): void
    {
        $bestseller = Product::orderByDesc('sold_count')->first();
        $this->get('/')->assertOk()->assertSee('Terlaris')->assertSee('Biografi')->assertSee($bestseller->title);
        $this->get('/books')->assertOk()->assertSee('Semua Buku');
        $this->get('/books?q=tere&sort=price_asc')->assertOk()->assertSee('Sagaras')->assertDontSee('Cantik Itu Luka');
        $this->get('/books?category=biografi')->assertOk()->assertSee('Wingit');
        $this->get('/books/cantik-itu-luka')->assertOk()->assertSee('Eka Kurniawan');
        $this->get('/store/kyread-official-store')->assertOk()->assertSee('Kyread Official Store');
        $this->get('/help')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_guest_is_redirected_from_protected_pages(): void
    {
        foreach (['/cart', '/checkout', '/orders', '/profile', '/seller'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_user_can_register_and_login_with_username_or_email(): void
    {
        $this->post('/register', [
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/');
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->post('/login', ['login' => 'budi', 'password' => 'rahasia123'])->assertRedirect('/');
        $this->assertAuthenticatedAs(User::firstWhere('username', 'budi'));

        $this->post('/logout');
        $this->post('/login', ['login' => 'budi@example.com', 'password' => 'salah'])->assertSessionHas('loginError');
        $this->assertGuest();
    }

    public function test_authenticated_pages_render_for_buyer(): void
    {
        $buyer = User::firstWhere('email', 'buyer@kyread.test');
        $order = $buyer->orders()->latest()->first();

        $this->actingAs($buyer)->get('/cart')->assertOk();
        $this->actingAs($buyer)->get('/orders')->assertOk()->assertSee($order->code);
        $this->actingAs($buyer)->get("/orders/{$order->code}")->assertOk();
        $this->actingAs($buyer)->get('/profile')->assertOk();
        // Pembeli tanpa toko diarahkan ke halaman buka toko.
        $this->actingAs($buyer)->get('/seller')->assertRedirect(route('seller.store.create'));
    }

    public function test_user_cannot_view_other_users_order(): void
    {
        $order = User::firstWhere('email', 'buyer@kyread.test')->orders()->first();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get("/orders/{$order->code}")->assertForbidden();
    }

    public function test_staging_label_only_appears_on_staging(): void
    {
        $this->get('/')->assertDontSee('env-badge', false);

        $this->app['env'] = 'staging';
        $this->get('/')->assertSee('env-badge', false)->assertSee('<title>[Staging]', false);
        $this->actingAs(User::firstWhere('email', 'seller@kyread.test'))->get('/seller')->assertSee('env-badge', false);
    }
}
