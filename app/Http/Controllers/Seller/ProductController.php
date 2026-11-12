<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        return view('seller.products.index', [
            'products' => $request->user()->store->products()
                ->with('category')
                ->filter($request->only('q', 'category', 'sort'))
                ->paginate(15)
                ->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('seller.products.form', [
            'product' => new Product(['is_active' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $request->user()->store->products()->create($data);

        return redirect()->route('seller.products.index')->with('success', 'Buku berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        Gate::authorize('manage', $product);

        return view('seller.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('manage', $product);

        $data = $this->validated($request);

        if ($data['title'] !== $product->title) {
            $data['slug'] = Product::uniqueSlug($data['title'], $product->id);
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($product);
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($product);
            $data['image'] = null;
        }

        $product->update($data);

        return redirect()->route('seller.products.index')->with('success', 'Buku berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('manage', $product);

        $this->deleteImage($product);
        $product->delete();

        return back()->with('success', 'Buku dihapus dari toko.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'author' => ['required', 'string', 'max:100'],
            'publisher' => ['nullable', 'string', 'max:100'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'published_year' => ['nullable', 'integer', 'min:1800', 'max:'.(now()->year + 1)],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'weight' => ['required', 'integer', 'min:1', 'max:50000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        return $data;
    }

    private function deleteImage(Product $product): void
    {
        // Gambar contoh dari seeder ada di public/img, jangan dihapus.
        if ($product->image && ! str_starts_with($product->image, 'img/')) {
            Storage::disk('public')->delete($product->image);
        }
    }
}
