<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->store) {
            return redirect()->route('seller.dashboard');
        }

        return view('seller.store.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->store, 409, 'Kamu sudah punya toko.');

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $request->user()->store()->create($data);

        return redirect()->route('seller.dashboard')->with('success', 'Toko berhasil dibuka. Yuk tambahkan buku pertamamu!');
    }

    public function edit(Request $request): View
    {
        return view('seller.store.edit', ['store' => $request->user()->store]);
    }

    public function update(Request $request): RedirectResponse
    {
        $store = $request->user()->store;
        $data = $this->validated($request, $store);

        if ($data['name'] !== $store->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $store->id);
        }

        if ($request->hasFile('logo')) {
            if ($store->logo) {
                Storage::disk('public')->delete($store->logo);
            }
            $data['logo'] = $request->file('logo')->store('stores', 'public');
        }

        $store->update($data);

        return back()->with('success', 'Profil toko diperbarui.');
    }

    private function validated(Request $request, ?Store $store = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:60', Rule::unique('stores')->ignore($store)],
            'description' => ['nullable', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:64'],
            'phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'logo' => ['nullable', 'image', 'max:1024'],
        ], ['phone.regex' => 'Format nomor HP tidak valid (contoh: 081234567890).']);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'toko';
        $slug = $base;
        $i = 2;

        while (Store::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-".$i++;
        }

        return $slug;
    }
}
