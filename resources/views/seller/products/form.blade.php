@extends('layouts.seller')

@php($editing = $product->exists)

@section('title', $editing ? __('Edit Buku') : __('Tambah Buku'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('seller.products.index') }}">{{ __('Daftar Buku') }}</a></li>
    <li class="breadcrumb-item active">{{ $editing ? __('Edit') : __('Tambah') }}</li>
@endsection

@section('content')
    <form action="{{ $editing ? route('seller.products.update', $product) : route('seller.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-start">{{ __('Sampul Buku') }}</h5>
                        <img class="img-fluid rounded shadow-sm mb-3" id="imgPreview" src="{{ $product->image_url }}" alt="" style="max-height: 320px">
                        <input class="form-control @error('image') is-invalid @enderror" id="image" name="image" type="file" data-preview="#imgPreview" accept="image/png,image/jpeg,image/webp">
                        @error('image')<div class="invalid-feedback text-start">{{ $message }}</div>@enderror
                        <small class="text-muted d-block mt-2">{{ __('JPG/PNG/WEBP, maks. 2 MB. Rasio 3:4 disarankan.') }}</small>
                        @if ($editing && $product->image)
                            <div class="form-check text-start mt-2">
                                <input class="form-check-input" id="remove_image" name="remove_image" type="checkbox" value="1">
                                <label class="form-check-label small" for="remove_image">{{ __('Hapus sampul saat ini') }}</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Informasi Buku') }}</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="title">{{ __('Judul') }} <span class="text-danger">*</span></label>
                                <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" type="text" value="{{ old('title', $product->title) }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="author">{{ __('Penulis') }} <span class="text-danger">*</span></label>
                                <input class="form-control @error('author') is-invalid @enderror" id="author" name="author" type="text" value="{{ old('author', $product->author) }}" required>
                                @error('author')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category_id">{{ __('Kategori') }} <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                    <option value="">{{ __('Pilih kategori') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @foreach ([
                                ['publisher', __('Penerbit'), 'text', 'col-md-6'],
                                ['isbn', 'ISBN', 'text', 'col-md-6'],
                                ['pages', __('Jumlah halaman'), 'number', 'col-md-6'],
                                ['published_year', __('Tahun terbit'), 'number', 'col-md-6'],
                            ] as [$field, $label, $type, $col])
                                <div class="{{ $col }}">
                                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                                    <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field, $product->$field) }}">
                                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                            <div class="col-12">
                                <label class="form-label" for="description">{{ __('Deskripsi') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="6" required>{{ old('description', $product->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <h5 class="card-title">{{ __('Harga, Stok & Pengiriman') }}</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="price">{{ __('Harga') }} <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">Rp</span>
                                    <input class="form-control @error('price') is-invalid @enderror" id="price" name="price" type="number" min="1000" step="500" value="{{ old('price', $product->price) }}" required>
                                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="compare_at_price">{{ __('Harga sebelum diskon') }}</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">Rp</span>
                                    <input class="form-control @error('compare_at_price') is-invalid @enderror" id="compare_at_price" name="compare_at_price" type="number" min="1000" step="500" value="{{ old('compare_at_price', $product->compare_at_price) }}" placeholder="{{ __('Opsional') }}">
                                    @error('compare_at_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-text">{{ __('Isi bila sedang diskon; ditampilkan sebagai harga coret.') }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="stock">{{ __('Stok') }} <span class="text-danger">*</span></label>
                                <input class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock ?? 1) }}" required>
                                @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="weight">{{ __('Berat') }} <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <input class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" type="number" min="1" value="{{ old('weight', $product->weight) }}" required>
                                    <span class="input-group-text">{{ __('gram') }}</span>
                                    @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active))>
                                    <label class="form-check-label" for="is_active">{{ __('Tampilkan di katalog (aktif)') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a class="btn btn-outline-secondary" href="{{ route('seller.products.index') }}">{{ __('Batal') }}</a>
                            <button class="btn btn-ky px-4" type="submit">{{ $editing ? __('Simpan Perubahan') : __('Tambah Buku') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
