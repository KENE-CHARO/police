<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function validated(array $keys = null)
    {
        $data = parent::validated($keys);
        // Remove current_password from the data returned for storage
        unset($data['current_password']);
        return $data;
    }
}
