<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only('q', 'category', 'min', 'max', 'promo', 'sort');

        $products = Product::active()
            ->with('category', 'store')
            ->withAvg('reviews', 'rating')
            ->filter($filters)
            ->paginate(20)
            ->withQueryString();

        return view('catalog.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
            'activeCategory' => isset($filters['category']) ? Category::firstWhere('slug', $filters['category']) : null,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active || request()->user()?->can('manage', $product), 404);

        $product->load('category', 'store')->loadAvg('reviews', 'rating')->loadCount('reviews');

        return view('catalog.show', [
            'product' => $product,
            'reviews' => $product->reviews()->with('user')->latest()->take(10)->get(),
            'vouchers' => $product->store->vouchers()->available()->orderBy('min_spend')->take(5)->get(),
            'related' => Product::active()
                ->with('category', 'store')
                ->withAvg('reviews', 'rating')
                ->whereKeyNot($product->id)
                ->where('category_id', $product->category_id)
                ->inRandomOrder()
                ->take(5)
                ->get(),
        ]);
    }

    public function store(Request $request, Store $store): View
    {
        return view('catalog.store', [
            'store' => $store->loadCount(['products' => fn ($q) => $q->active()]),
            'vouchers' => $store->vouchers()->available()->orderBy('min_spend')->get(),
            'products' => $store->products()->active()->with('category')->withAvg('reviews', 'rating')
                ->filter($request->only('q', 'sort'))->paginate(20)->withQueryString(),
        ]);
    }
}
