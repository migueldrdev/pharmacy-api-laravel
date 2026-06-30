<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->user()?->id;

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'password' => ['nullable', 'string', 'min:8', Password::defaults()],
            'role_id' => 'sometimes|integer|exists:roles,id',
            'active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'El nombre no debe exceder 255 caracteres',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'El email ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'role_id.exists' => 'El rol seleccionado no existe',
        ];
    }
}
