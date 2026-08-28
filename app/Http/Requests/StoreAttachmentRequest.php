<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => ['required','file','max:10240','mimes:jpg,jpeg,png,pdf,doc,docx,txt'],
        ];
    }
}
