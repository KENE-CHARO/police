<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlainteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'titre' => ['sometimes','string','max:191'],
            'description' => ['nullable','string'],
            'commissariat_id' => ['nullable','exists:commissariats,id'],
            'statut' => ['sometimes','string'],
        ];
    }
}
