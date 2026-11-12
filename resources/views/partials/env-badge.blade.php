{{-- Penanda lingkungan non-produksi (APP_ENV=staging), agar tidak tertukar dengan situs asli. --}}
@if (app()->environment('staging'))
    <span class="env-badge" title="Situs uji coba. Data dan pembayaran bukan sungguhan.">Staging</span>
@endif
