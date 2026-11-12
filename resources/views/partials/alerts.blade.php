@foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $key => $type)
    @if (session($key))
        <div class="alert alert-{{ $type }} alert-dismissible fade show" role="alert">
            {{ session($key) }}
            <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Tutup"></button>
        </div>
    @endif
@endforeach

@if ($errors->hasAny(['cart', 'status', 'tracking_number']))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first('cart') ?: ($errors->first('status') ?: $errors->first('tracking_number')) }}
        <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Tutup"></button>
    </div>
@endif
