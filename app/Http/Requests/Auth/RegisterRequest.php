<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|string|in:citoyen,agent_accueil,enqueteur,admin',
            'commissariat_id' => 'nullable|integer|exists:commissariats,id',
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('password')) {
            // nothing for now
        }
    }
}
