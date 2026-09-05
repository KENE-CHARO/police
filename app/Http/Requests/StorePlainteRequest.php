<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlainteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'commissariat_id' => ['nullable', 'exists:commissariats,id'],
            'paid' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'in:mobile,carte'],
            'payment_phone' => ['required_if:payment_method,mobile', 'string', 'max:20'],
            'payment_operator' => ['required_if:payment_method,mobile', 'in:mtn,orange'],
            'payment_amount' => ['required_if:payment_method,mobile,carte', 'integer', 'min:1'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt'],
        ];
    }
}
