<?php

namespace App\Http\Controllers\Api;

use App\Enums\Courier;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    /** Data statis untuk aplikasi: kurir, metode bayar, dan banner. */
    public function meta(): JsonResponse
    {
        return response()->json([
            'couriers' => collect(Courier::cases())->map(fn (Courier $c) => [
                'code' => $c->value,
                'label' => $c->label(),
                'rate_per_kg' => $c->ratePerKg(),
                'estimate' => $c->estimate(),
                'logo_url' => asset($c->logo()),
            ]),
            'payment_methods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $p) => [
                'code' => $p->value,
                'label' => $p->label(),
                'logo_url' => asset($p->logo()),
            ]),
            'banners' => collect(range(1, 8))->map(fn (int $i) => asset("img/promo-banner/banner{$i}.jpg")),
        ]);
    }

    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::withCount(['products' => fn ($q) => $q->active()])->orderBy('name')->get()
        );
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $products = Product::active()
            ->with('category', 'store')
            ->withAvg('reviews', 'rating')
            ->filter($request->only('q', 'category', 'min', 'max', 'sort'))
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active || $request->user('sanctum')?->can('manage', $product), 404);

        $product->load('category', 'store')->loadAvg('reviews', 'rating')->loadCount('reviews');

        return response()->json([
            'data' => new ProductResource($product),
            'reviews' => ReviewResource::collection($product->reviews()->with('user')->latest()->take(10)->get()),
            'related' => ProductResource::collection(
                Product::active()->with('category', 'store')->withAvg('reviews', 'rating')
                    ->whereKeyNot($product->id)->where('category_id', $product->category_id)
                    ->inRandomOrder()->take(6)->get()
            ),
        ]);
    }
}
