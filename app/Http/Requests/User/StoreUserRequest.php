<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends BaseFormRequest
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
            'password' => ['required', 'string', 'min:8', Password::defaults()],
            'role_id' => 'required|integer|exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido',
            'name.max' => 'El nombre no debe exceder 255 caracteres',
            'email.required' => 'El email es requerido',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'El email ya está registrado',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'role_id.required' => 'El rol es requerido',
            'role_id.exists' => 'El rol seleccionado no existe',
        ];
    }
}
