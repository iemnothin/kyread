<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProductResource::collection(
            $request->user()->wishlist()
                ->with('category', 'store')
                ->withAvg('reviews', 'rating')
                ->orderByPivot('created_at', 'desc')
                ->paginate(20)
        );
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $request->user()->wishlist()->syncWithoutDetaching([$product->id]);

        return response()->json(['wishlisted' => true]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->wishlist()->detach($product->id);

        return response()->json(['wishlisted' => false]);
    }
}
