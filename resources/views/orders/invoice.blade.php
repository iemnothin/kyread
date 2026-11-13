<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::htmlLang() }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Invoice :code', ['code' => $order->code]) }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/brand.svg') }}" type="image/svg+xml">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7fb; color: #1f2937; font: 14px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .sheet { max-width: 800px; margin: 24px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(15, 23, 42, .08); }
        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; border-bottom: 3px solid #43aaff; padding-bottom: 16px; }
        .brand { display: flex; align-items: center; gap: 10px; font-size: 22px; font-weight: 700; color: #1f8ae6; }
        .brand img { width: 36px; height: 36px; }
        h1 { margin: 0; font-size: 20px; letter-spacing: .08em; text-align: right; }
        .muted { color: #6b7280; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 20px 0; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .num { text-align: right; white-space: nowrap; }
        tfoot td { border: 0; padding: 4px 8px; }
        tfoot tr.total td { font-size: 16px; font-weight: 700; border-top: 2px solid #1f2937; padding-top: 10px; }
        .status { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0369a1; }
        .status.pending { background: #fef3c7; color: #92400e; }
        .status.cancelled { background: #fee2e2; color: #991b1b; }
        .actions { max-width: 800px; margin: 16px auto 0; display: flex; justify-content: flex-end; gap: 8px; padding: 0 16px; }
        .btn { border: 0; border-radius: 8px; padding: 8px 16px; font: inherit; font-weight: 600; cursor: pointer; background: #43aaff; color: #fff; text-decoration: none; }
        .btn.light { background: #fff; color: #1f2937; border: 1px solid #d1d5db; }
        footer { margin-top: 28px; font-size: 12px; color: #6b7280; text-align: center; }
        @media (max-width: 600px) { .sheet { padding: 20px; margin: 12px; } h1 { text-align: left; } }
        @media print {
            body { background: #fff; }
            .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        @auth
            <a class="btn light" href="{{ auth()->user()->can('manage', $order) ? route('seller.orders.show', $order) : route('orders.show', $order) }}">{{ __('Kembali') }}</a>
        @endauth
        <button class="btn" type="button" onclick="window.print()">{{ __('Cetak / Simpan PDF') }}</button>
    </div>

    <main class="sheet">
        <div class="top">
            <div class="brand"><img src="{{ asset('img/brand.svg') }}" alt=""> {{ config('app.name') }}</div>
            <div>
                <h1>INVOICE</h1>
                <div class="muted" style="text-align: right">{{ $order->code }}</div>
            </div>
        </div>

        <div class="grid">
            <div>
                <div class="label">{{ __('Penjual') }}</div>
                <b>{{ $order->store->name }}</b><br>
                <span class="muted">{{ $order->store->city }}</span>
            </div>
            <div>
                <div class="label">{{ __('Pembeli') }}</div>
                <b>{{ $order->recipient_name }}</b> · {{ $order->recipient_phone }}<br>
                <span class="muted">{{ $order->full_address }}</span>
            </div>
            <div>
                <div class="label">{{ __('Tanggal pesanan') }}</div>
                {{ __(':date WIB', ['date' => $order->created_at->translatedFormat('d F Y, H:i')]) }}
                <div class="label" style="margin-top: 8px">{{ __('Status') }}</div>
                <span class="status {{ $order->status->value }}">{{ $order->status->label() }}</span>
                @if ($order->paid_at)<div class="muted">{{ __('Dibayar :date', ['date' => $order->paid_at->translatedFormat('d M Y H:i')]) }}</div>@endif
            </div>
        </div>

        <table>
            <thead>
                <tr><th>{{ __('Buku') }}</th><th class="num">{{ __('Harga') }}</th><th class="num">{{ __('Jumlah') }}</th><th class="num">{{ __('Subtotal') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td class="num">@rupiah($item->price)</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">@rupiah($item->subtotal)</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="3" class="num muted">{{ __('Subtotal') }}</td><td class="num">@rupiah($order->subtotal)</td></tr>
                <tr><td colspan="3" class="num muted">{{ __('Ongkos kirim (:courier)', ['courier' => $order->courier->label()]) }}</td><td class="num">@rupiah($order->shipping_cost)</td></tr>
                @if ($order->discount > 0)
                    <tr><td colspan="3" class="num muted">{{ __('Voucher :code', ['code' => $order->voucher_code]) }}</td><td class="num">−@rupiah($order->discount)</td></tr>
                @endif
                <tr class="total"><td colspan="3" class="num">{{ __('Total') }}</td><td class="num">@rupiah($order->total)</td></tr>
            </tfoot>
        </table>

        <div class="grid">
            <div><div class="label">{{ __('Metode pembayaran') }}</div>{{ $order->payment_method->label() }}</div>
            <div><div class="label">{{ __('Kurir') }}</div>{{ $order->courier->label() }}@if ($order->tracking_number) · {{ __('resi') }} <b>{{ $order->tracking_number }}</b>@endif</div>
        </div>

        <footer>{{ __('Invoice ini dibuat otomatis oleh :app dan sah tanpa tanda tangan.', ['app' => config('app.name')]) }}</footer>
    </main>
</body>
</html>
