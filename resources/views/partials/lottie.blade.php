{{--
    Animasi Lottie dengan ikon cadangan (lihat resources/js/lottie.js).
    @include('partials.lottie', ['name' => 'empty', 'size' => 160, 'icon' => 'bi-bag', 'repeat' => true, 'class' => ''])
    Catatan: parameter bernama "repeat", bukan "loop" ($loop milik @foreach).
--}}
<div class="lottie {{ $class ?? '' }}" data-lottie="{{ $name }}" data-loop="{{ ($repeat ?? true) ? 'true' : 'false' }}"
    style="width: {{ $size ?? 160 }}px; height: {{ $size ?? 160 }}px" aria-hidden="true">
    @isset($icon)<i class="bi {{ $icon }} lottie-fallback"></i>@endisset
    {{ $slot ?? '' }}
</div>
