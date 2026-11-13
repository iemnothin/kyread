<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        return view('wishlist.index', [
            'products' => $request->user()->wishlist()
                ->with('category', 'store')
                ->withAvg('reviews', 'rating')
                ->orderByPivot('created_at', 'desc')
                ->paginate(20),
        ]);
    }

    /** Tambah/hapus dari wishlist. Dipanggil lewat form biasa atau fetch() (JSON). */
    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $result = $request->user()->wishlist()->toggle($product->id);
        $added = $result['attached'] !== [];

        if ($request->expectsJson()) {
            return response()->json(['wishlisted' => $added, 'count' => $request->user()->wishlist()->count()]);
        }

        return back()->with('success', $added ? __('Ditambahkan ke wishlist.') : __('Dihapus dari wishlist.'));
    }
}
