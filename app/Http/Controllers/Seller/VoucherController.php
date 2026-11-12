<?php

namespace App\Http\Controllers\Seller;

use App\Enums\VoucherType;
use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        return view('seller.vouchers.index', [
            'vouchers' => $request->user()->store->vouchers()->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('seller.vouchers.form', [
            'voucher' => new Voucher(['type' => VoucherType::Percent, 'is_active' => true, 'min_spend' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $voucher = $request->user()->store->vouchers()->create($this->validated($request));

        return redirect()->route('seller.vouchers.index')->with('success', "Voucher {$voucher->code} dibuat.");
    }

    public function edit(Request $request, Voucher $voucher): View
    {
        $this->authorizeOwner($request, $voucher);

        return view('seller.vouchers.form', ['voucher' => $voucher]);
    }

    public function update(Request $request, Voucher $voucher): RedirectResponse
    {
        $this->authorizeOwner($request, $voucher);
        $voucher->update($this->validated($request, $voucher));

        return redirect()->route('seller.vouchers.index')->with('success', "Voucher {$voucher->code} diperbarui.");
    }

    public function destroy(Request $request, Voucher $voucher): RedirectResponse
    {
        $this->authorizeOwner($request, $voucher);
        $voucher->delete();

        return back()->with('success', "Voucher {$voucher->code} dihapus.");
    }

    private function authorizeOwner(Request $request, Voucher $voucher): void
    {
        abort_unless($voucher->store_id === $request->user()->store->id, 403);
    }

    private function validated(Request $request, ?Voucher $voucher = null): array
    {
        $request->merge(['code' => Voucher::normalize($request->input('code'))]);

        $isPercent = $request->input('type') === VoucherType::Percent->value;

        $data = $request->validate([
            'code' => ['required', 'alpha_num:ascii', 'min:4', 'max:20', Rule::unique('vouchers', 'code')->ignore($voucher)],
            'type' => ['required', Rule::enum(VoucherType::class)],
            'value' => ['required', 'integer', 'min:1', $isPercent ? 'max:90' : 'max:10000000'],
            'min_spend' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'max_discount' => ['nullable', 'integer', 'min:1000', 'max:10000000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => array_filter(['nullable', 'date', 'after:now', $request->filled('starts_at') ? 'after:starts_at' : null]),
        ], array_filter([
            'code.alpha_num' => 'Kode voucher hanya boleh berisi huruf dan angka.',
            'value.max' => $isPercent ? 'Diskon persentase maksimal 90%.' : null,
        ]));

        $data['min_spend'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');
        if ($data['type'] === VoucherType::Fixed->value) {
            $data['max_discount'] = null;
        }

        return $data;
    }
}
