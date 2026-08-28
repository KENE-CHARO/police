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
            'titre' => ['required','string','max:191'],
            'description' => ['nullable','string'],
            'commissariat_id' => ['nullable','exists:commissariats,id'],
            'attachments' => ['sometimes','array'],
            'attachments.*' => ['file','max:10240','mimes:jpg,jpeg,png,pdf,doc,docx,txt'],
        ];
    }
}
