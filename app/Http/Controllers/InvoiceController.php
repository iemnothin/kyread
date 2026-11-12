<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Invoice siap cetak. Bisa dibuka pembeli/penjual yang login, atau lewat tautan
     * bertanda tangan (dipakai aplikasi Android yang tidak punya sesi web).
     */
    public function __invoke(Request $request, Order $order): View
    {
        $user = $request->user();
        $allowed = $request->hasValidSignature()
            || ($user && ($user->can('view', $order) || $user->can('manage', $order)));

        abort_unless($allowed, $user ? 403 : 401);

        return view('orders.invoice', ['order' => $order->load('store', 'items', 'user')]);
    }

    public static function signedUrl(Order $order): string
    {
        return URL::temporarySignedRoute('orders.invoice', now()->addHour(), ['order' => $order->code]);
    }
}
