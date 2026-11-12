<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery\MockInterface;
use Tests\TestCase;

class SmartFeaturesTest extends TestCase
{
    use RefreshDatabase;

    // --- Saran kata ---------------------------------------------------------------

    public function test_suggestions_update_per_letter(): void
    {
        Product::factory()->create(['title' => 'Bumi', 'author' => 'Tere Liye']);
        Product::factory()->create(['title' => 'Bulan', 'author' => 'Tere Liye']);
        Product::factory()->create(['title' => 'Laskar Pelangi', 'author' => 'Andrea Hirata']);
        Product::factory()->create(['title' => 'Buku Rahasia', 'is_active' => false]);

        $this->getJson('/api/v1/search/suggest?q=')->assertOk()->assertJsonCount(0, 'data');

        $titles = fn (string $q) => collect($this->getJson('/api/v1/search/suggest?q='.urlencode($q))->json('data'))
            ->where('type', 'title')->pluck('label')->all();

        $this->assertEqualsCanonicalizing(['Bumi', 'Bulan'], $titles('Bu'));
        $this->assertSame(['Bumi'], $titles('Bum'));

        $this->getJson('/search/suggest?q=tere') // endpoint web
            ->assertOk()
            ->assertJsonFragment(['type' => 'author', 'label' => 'Tere Liye']);
    }

    // --- Cari dengan gambar -----------------------------------------------------------

    public function test_image_search_returns_identified_book_and_matches(): void
    {
        Product::factory()->create(['title' => 'Laskar Pelangi', 'author' => 'Andrea Hirata']);
        Product::factory()->create(['title' => 'Bumi']);

        $this->mock(AiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('identifyBook')->once()->andReturn([
                'title' => 'Laskar Pelangi', 'author' => 'Andrea Hirata',
                'keywords' => ['Belitung'], 'description' => 'Sampul novel Laskar Pelangi.',
            ]);
            $mock->shouldReceive('matchProducts')->once()->passthru();
        });

        $this->post('/api/v1/search/image', ['image' => UploadedFile::fake()->image('cover.jpg', 300, 400)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('identified.title', 'Laskar Pelangi')
            ->assertJsonPath('query', 'Laskar Pelangi')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laskar Pelangi');
    }

    public function test_image_search_validates_file(): void
    {
        $this->post('/api/v1/search/image', ['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    }

    public function test_ai_features_report_unavailable_without_api_key(): void
    {
        config(['services.anthropic.key' => null]);

        $this->postJson('/api/v1/assistant', ['messages' => [['role' => 'user', 'content' => 'Halo']]])
            ->assertStatus(503)
            ->assertJsonPath('message', 'Fitur AI belum diaktifkan di server.');
    }

    // --- Asisten AI ---------------------------------------------------------------------

    public function test_assistant_replies_with_recommended_products(): void
    {
        $book = Product::factory()->create(['title' => 'Misteri Rumah Tua']);

        $this->mock(AiService::class, function (MockInterface $mock) use ($book) {
            $mock->shouldReceive('chat')
                ->once()
                ->withArgs(fn (array $history) => count($history) === 1 && $history[0]['role'] === 'user')
                ->andReturn(['reply' => 'Coba "Misteri Rumah Tua".', 'products' => collect([$book->load('category', 'store')])]);
        });

        $this->postJson('/api/v1/assistant', [
            'messages' => [
                ['role' => 'assistant', 'content' => 'Sapaan lama yang harus dibuang'],
                ['role' => 'user', 'content' => 'Rekomendasi novel misteri'],
            ],
        ])->assertOk()
            ->assertJsonPath('reply', 'Coba "Misteri Rumah Tua".')
            ->assertJsonPath('products.0.slug', $book->slug);
    }

    public function test_assistant_validates_history(): void
    {
        $this->postJson('/api/v1/assistant', ['messages' => []])->assertUnprocessable();
        $this->postJson('/api/v1/assistant', ['messages' => [['role' => 'system', 'content' => 'x']]])->assertUnprocessable();
        $this->get('/assistant')->assertOk()->assertSee('Tanya Kyra');
    }

    // --- Login dengan Google -------------------------------------------------------------

    public function test_android_google_login_creates_account_from_verified_id_token(): void
    {
        config(['services.google.client_id' => 'web-client.apps.googleusercontent.com']);
        Http::fake(['oauth2.googleapis.com/*' => Http::response([
            'aud' => 'web-client.apps.googleusercontent.com',
            'iss' => 'https://accounts.google.com',
            'sub' => 'google-123',
            'email' => 'dewi.lestari@gmail.com',
            'email_verified' => 'true',
            'name' => 'Dewi Lestari',
            'exp' => (string) (time() + 3600),
        ])]);

        $this->postJson('/api/v1/auth/google', ['id_token' => 'token'])
            ->assertOk()
            ->assertJsonPath('user.email', 'dewi.lestari@gmail.com')
            ->assertJsonPath('user.username', 'dewilestari')
            ->assertJsonStructure(['token']);

        // Login kedua memakai akun yang sama.
        $this->postJson('/api/v1/auth/google', ['id_token' => 'token'])->assertOk();
        $this->assertSame(1, User::where('email', 'dewi.lestari@gmail.com')->count());
        $this->assertNull(User::first()->password);
    }

    public function test_android_google_login_rejects_token_for_other_app(): void
    {
        config(['services.google.client_id' => 'web-client.apps.googleusercontent.com']);
        Http::fake(['oauth2.googleapis.com/*' => Http::response([
            'aud' => 'someone-else', 'iss' => 'accounts.google.com', 'sub' => '1',
            'email' => 'x@gmail.com', 'email_verified' => 'true', 'exp' => (string) (time() + 3600),
        ])]);

        $this->postJson('/api/v1/auth/google', ['id_token' => 'token'])->assertUnprocessable()->assertJsonValidationErrors('id_token');
        $this->assertSame(0, User::count());
    }

    public function test_web_google_login_links_existing_account_by_email(): void
    {
        config(['services.google.client_id' => 'web-client', 'services.google.client_secret' => 'secret']);
        $existing = User::factory()->create(['email' => 'sari@gmail.com']);

        $google = (new SocialiteUser)->map(['id' => 'g-77', 'email' => 'sari@gmail.com', 'name' => 'Sari', 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($google);

        $this->get('/auth/google/callback')->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('g-77', $existing->fresh()->google_id);
    }

    public function test_google_user_can_set_password_without_current_password(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['password' => null])->save();

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Buat Password');
        $this->actingAs($user)->put('/profile/password', ['password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->password);
    }

    public function test_login_page_shows_google_button_only_when_configured(): void
    {
        config(['services.google.client_id' => null]);
        $this->get('/login')->assertDontSee('dengan Google');

        config(['services.google.client_id' => 'web-client']);
        app('view')->share('googleEnabled', true);
        $this->get('/login')->assertSee('Masuk dengan Google');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Category::create(['name' => 'Novel', 'slug' => 'novel']);
        config(['services.anthropic.key' => 'test-key']);
    }
}
