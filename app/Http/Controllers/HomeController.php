<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $base = Product::active()->with('category', 'store')->withAvg('reviews', 'rating');

        return view('home', [
            'categories' => Category::withCount(['products' => fn ($q) => $q->active()])->orderBy('name')->get(),
            'bestsellers' => (clone $base)->orderByDesc('sold_count')->take(10)->get(),
            'latest' => (clone $base)->latest()->take(10)->get(),
            'deals' => (clone $base)->onSale()->filter(['sort' => 'discount'])->take(10)->get(),
        ]);
    }
}
