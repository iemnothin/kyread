<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Pratinjau voucher terhadap isi keranjang; dipakai halaman checkout web dan aplikasi Android. */
class VoucherController extends Controller
{
    public function __invoke(Request $request, CheckoutService $checkout): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);

        ['voucher' => $voucher, 'store_id' => $storeId, 'discount' => $discount] = $checkout->previewVoucher($request->user(), $data['code']);

        return response()->json([
            'code' => $voucher->code,
            'store_id' => $storeId,
            'store_name' => $voucher->store->name,
            'summary' => $voucher->summary,
            'discount' => $discount,
            'message' => 'Voucher '.$voucher->code.' dipakai: hemat Rp '.number_format($discount, 0, ',', '.').'.',
        ]);
    }
}
