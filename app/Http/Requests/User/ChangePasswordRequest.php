<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required',
            'password' => ['required', 'string', 'min:8', Password::defaults(), 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'La contraseña actual es requerida',
            'password.required' => 'La nueva contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ];
    }
}
