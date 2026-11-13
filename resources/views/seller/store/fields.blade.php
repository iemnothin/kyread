<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="mb-3">
        <label class="form-label" for="name">{{ __('Nama toko') }} <span class="text-danger">*</span></label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $store->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="city">{{ __('Kota asal pengiriman') }} <span class="text-danger">*</span></label>
            <input class="form-control @error('city') is-invalid @enderror" id="city" name="city" type="text" value="{{ old('city', $store->city ?? auth()->user()->city) }}" required>
            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="phone">{{ __('No. HP toko') }}</label>
            <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="text" value="{{ old('phone', $store->phone ?? auth()->user()->phone) }}">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="description">{{ __('Deskripsi toko') }}</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $store->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-4">
        <label class="form-label" for="logo">{{ __('Logo toko (maks. 1 MB)') }}</label>
        <input class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" type="file" accept="image/*">
        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-ky px-4" type="submit">{{ $submit }}</button>
</form>
