<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseFormRequest;

class StoreRoleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es requerido',
            'name.max' => 'El nombre no debe exceder 255 caracteres',
            'name.unique' => 'El nombre del rol ya existe',
            'description.max' => 'La descripción no debe exceder 500 caracteres',
        ];
    }
}
