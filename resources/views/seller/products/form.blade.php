@extends('layouts.seller')

@php($editing = $product->exists)

@section('title', $editing ? 'Edit Buku' : 'Tambah Buku')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('seller.products.index') }}">Daftar Buku</a></li>
    <li class="breadcrumb-item active">{{ $editing ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')
    <form action="{{ $editing ? route('seller.products.update', $product) : route('seller.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-start">Sampul Buku</h5>
                        <img class="img-fluid rounded shadow-sm mb-3" id="imgPreview" src="{{ $product->image_url }}" alt="" style="max-height: 320px">
                        <input class="form-control @error('image') is-invalid @enderror" id="image" name="image" type="file" data-preview="#imgPreview" accept="image/png,image/jpeg,image/webp">
                        @error('image')<div class="invalid-feedback text-start">{{ $message }}</div>@enderror
                        <small class="text-muted d-block mt-2">JPG/PNG/WEBP, maks. 2 MB. Rasio 3:4 disarankan.</small>
                        @if ($editing && $product->image)
                            <div class="form-check text-start mt-2">
                                <input class="form-check-input" id="remove_image" name="remove_image" type="checkbox" value="1">
                                <label class="form-check-label small" for="remove_image">Hapus sampul saat ini</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informasi Buku</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="title">Judul <span class="text-danger">*</span></label>
                                <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" type="text" value="{{ old('title', $product->title) }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="author">Penulis <span class="text-danger">*</span></label>
                                <input class="form-control @error('author') is-invalid @enderror" id="author" name="author" type="text" value="{{ old('author', $product->author) }}" required>
                                @error('author')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category_id">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                    <option value="">Pilih kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @foreach ([
                                ['publisher', 'Penerbit', 'text', 'col-md-6'],
                                ['isbn', 'ISBN', 'text', 'col-md-6'],
                                ['pages', 'Jumlah halaman', 'number', 'col-md-6'],
                                ['published_year', 'Tahun terbit', 'number', 'col-md-6'],
                            ] as [$field, $label, $type, $col])
                                <div class="{{ $col }}">
                                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                                    <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field, $product->$field) }}">
                                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                            <div class="col-12">
                                <label class="form-label" for="description">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="6" required>{{ old('description', $product->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <h5 class="card-title">Harga, Stok &amp; Pengiriman</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="price">Harga <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">Rp</span>
                                    <input class="form-control @error('price') is-invalid @enderror" id="price" name="price" type="number" min="1000" step="500" value="{{ old('price', $product->price) }}" required>
                                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="stock">Stok <span class="text-danger">*</span></label>
                                <input class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock ?? 1) }}" required>
                                @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="weight">Berat <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <input class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" type="number" min="1" value="{{ old('weight', $product->weight) }}" required>
                                    <span class="input-group-text">gram</span>
                                    @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active))>
                                    <label class="form-check-label" for="is_active">Tampilkan di katalog (aktif)</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a class="btn btn-outline-secondary" href="{{ route('seller.products.index') }}">Batal</a>
                            <button class="btn btn-ky px-4" type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Buku' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
