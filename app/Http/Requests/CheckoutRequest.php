<?php

namespace App\Http\Requests;

use App\Enums\Courier;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public const string PHONE_REGEX = '/^(\+62|62|0)8[0-9]{7,12}$/';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:60'],
            'recipient_phone' => ['required', 'regex:'.self::PHONE_REGEX],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'zipcode' => ['required', 'digits:5'],
            'courier' => ['required', Rule::enum(Courier::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:255'],
            'voucher_code' => ['nullable', 'string', 'max:20'],
            'save_address' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['recipient_phone.regex' => __('Format nomor HP tidak valid (contoh: 081234567890).')];
    }

    /** Simpan alamat pengiriman sebagai alamat utama di profil bila diminta. */
    public function saveAddressIfRequested(): void
    {
        if ($this->boolean('save_address')) {
            $this->user()->update([
                'phone' => $this->validated('recipient_phone'),
                'street' => $this->validated('street'),
                'city' => $this->validated('city'),
                'province' => $this->validated('province'),
                'zipcode' => $this->validated('zipcode'),
            ]);
        }
    }
}
