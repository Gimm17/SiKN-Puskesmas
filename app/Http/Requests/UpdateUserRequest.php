<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user');
        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', "unique:users,email,{$userId}"],
            'role'     => ['required', 'in:admin,petugas'],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }
}
