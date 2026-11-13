<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Locales;
use App\Support\TranslationKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_ui_text_is_translated_with_matching_placeholders(): void
    {
        $missing = array_filter(TranslationKeys::missing());
        $this->assertSame([], $missing, 'Teks belum diterjemahkan: '.json_encode($missing, JSON_UNESCAPED_UNICODE));
        $this->assertSame([], TranslationKeys::placeholderMismatches());

        $this->artisan('i18n:check')->assertSuccessful();
    }

    public function test_switching_language_on_the_web(): void
    {
        Product::factory()->create(['title' => 'Bumi']);

        $this->get('/')->assertSee('Baru Masuk')->assertSee('lang="id"', false);

        $this->get('/locale/ja')->assertRedirect()->assertCookie(Locales::COOKIE, 'ja', encrypted: false);
        $this->get('/')->assertSee('新着')->assertDontSee('Baru Masuk')->assertSee('lang="ja"', false);

        // Pilihan bertahan lewat cookie walau sesi baru (mis. setelah logout).
        $this->flushSession();
        $this->withUnencryptedCookie(Locales::COOKIE, 'en')->get('/')->assertSee('New Arrivals');

        $this->get('/locale/xx')->assertNotFound();
    }

    public function test_arabic_is_right_to_left(): void
    {
        $this->get('/locale/ar');
        $this->get('/')->assertSee('dir="rtl"', false)->assertSee('وصل حديثًا');
    }

    public function test_api_follows_x_locale_header(): void
    {
        $this->postJson('/api/v1/auth/login', ['login' => '', 'password' => ''], ['X-Locale' => 'es'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.login.0', 'El campo correo o nombre de usuario es obligatorio.');

        $book = Product::factory()->create();
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.store', $book));
        $this->actingAs($buyer)->post(route('checkout.store'), [
            'recipient_name' => 'Budi', 'recipient_phone' => '081234567890', 'street' => 'Jl. Merdeka 1',
            'city' => 'Depok', 'province' => 'Jawa Barat', 'zipcode' => '16424', 'courier' => 'jne', 'payment_method' => 'bca',
        ]);
        $code = Order::firstOrFail()->code;

        Sanctum::actingAs($buyer);
        $this->getJson("/api/v1/orders/{$code}", ['X-Locale' => 'en'])->assertJsonPath('data.status_label', 'Awaiting Payment');
        $this->getJson("/api/v1/orders/{$code}", ['X-Locale' => 'ko'])->assertJsonPath('data.status_label', '결제 대기');
    }

    public function test_notifications_are_rendered_in_the_readers_language(): void
    {
        $book = Product::factory()->create();
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.store', $book));
        $this->actingAs($buyer)->post(route('checkout.store'), [
            'recipient_name' => 'Budi', 'recipient_phone' => '081234567890', 'street' => 'Jl. Merdeka 1',
            'city' => 'Depok', 'province' => 'Jawa Barat', 'zipcode' => '16424', 'courier' => 'jne', 'payment_method' => 'bca',
        ]);
        $seller = $book->store->user;
        $code = Order::firstOrFail()->code;

        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/notifications', ['X-Locale' => 'en'])
            ->assertJsonPath('data.0.title', 'New order received')
            ->assertJsonPath('data.0.body', "Order {$code} is awaiting the buyer's payment.");
        $this->getJson('/api/v1/notifications')->assertJsonPath('data.0.title', 'Pesanan baru masuk');
    }
}
