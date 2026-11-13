<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'store_id' => Store::factory(),
            'category_id' => fn () => Category::query()->value('id') ?? Category::create(['name' => 'Novel', 'slug' => 'novel'])->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'pages' => fake()->numberBetween(80, 600),
            'published_year' => fake()->numberBetween(1990, 2026),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(4, 20) * 5_000,
            'weight' => fake()->numberBetween(150, 900),
            'stock' => fake()->numberBetween(5, 50),
            'is_active' => true,
        ];
    }
}
