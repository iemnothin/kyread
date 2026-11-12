<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:60'],
            'nickname' => ['nullable', 'string', 'max:30'],
            'username' => ['required', 'alpha_dash', 'min:4', 'max:30', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'regex:'.CheckoutRequest::PHONE_REGEX, Rule::unique('users')->ignore($user)],
            'about' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:1024'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:64'],
            'province' => ['nullable', 'string', 'max:64'],
            'zipcode' => ['nullable', 'digits:5'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Format nomor HP tidak valid (contoh: 081234567890).'];
    }
}
