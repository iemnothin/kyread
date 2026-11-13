<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_a_store(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('seller.store.create'))->assertOk();
        $this->actingAs($user)->post(route('seller.store.store'), ['name' => 'Toko Buku Senja', 'city' => 'Bogor'])
            ->assertRedirect(route('seller.dashboard'));

        // fresh(): instance $user di test masih menyimpan relasi store = null dari request sebelumnya.
        $user = $user->fresh();
        $this->assertSame('toko-buku-senja', $user->store->slug);
        $this->actingAs($user)->get(route('seller.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('seller.store.create'))->assertRedirect(route('seller.dashboard'));
    }

    public function test_seller_can_create_update_and_delete_a_book(): void
    {
        Storage::fake('public');
        $store = Store::factory()->create();
        $category = Category::create(['name' => 'Novel', 'slug' => 'novel']);

        $this->actingAs($store->user)->get(route('seller.products.create'))->assertOk();
        $this->actingAs($store->user)->post(route('seller.products.store'), [
            'category_id' => $category->id,
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'description' => 'Kisah sepuluh anak Belitung.',
            'price' => 89_000,
            'weight' => 400,
            'stock' => 10,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('cover.jpg', 300, 400),
        ])->assertRedirect(route('seller.products.index'))->assertSessionHasNoErrors();

        $product = Product::firstWhere('slug', 'laskar-pelangi');
        $this->assertNotNull($product);
        Storage::disk('public')->assertExists($product->image);

        $this->actingAs($store->user)->get(route('seller.products.index'))->assertOk()->assertSee('Laskar Pelangi');
        $this->actingAs($store->user)->get(route('seller.products.edit', $product))->assertOk();

        $this->actingAs($store->user)->put(route('seller.products.update', $product), [
            'category_id' => $category->id,
            'title' => 'Laskar Pelangi (Edisi Baru)',
            'author' => 'Andrea Hirata',
            'description' => 'Edisi baru.',
            'price' => 99_000,
            'weight' => 400,
            'stock' => 7,
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('laskar-pelangi-edisi-baru', $product->slug);
        $this->assertFalse($product->is_active); // checkbox tidak dicentang

        $this->actingAs($store->user)->delete(route('seller.products.destroy', $product));
        $this->assertModelMissing($product);
    }

    public function test_seller_cannot_edit_other_sellers_book(): void
    {
        $product = Product::factory()->create();
        $other = Store::factory()->create();

        $this->actingAs($other->user)->get(route('seller.products.edit', $product))->assertForbidden();
        $this->actingAs($other->user)->delete(route('seller.products.destroy', $product))->assertForbidden();
    }

    public function test_inactive_books_are_hidden_from_catalog(): void
    {
        $product = Product::factory()->create(['is_active' => false, 'title' => 'Buku Rahasia']);

        $this->get(route('books.index'))->assertDontSee('Buku Rahasia');
        $this->get(route('books.show', $product))->assertNotFound();
        $this->actingAs($product->store->user)->get(route('books.show', $product))->assertOk();
    }
}
