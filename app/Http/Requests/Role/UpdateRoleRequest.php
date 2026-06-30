<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseFormRequest;

class UpdateRoleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $roleId,
            'description' => 'nullable|string|max:500',
            'active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'El nombre no debe exceder 255 caracteres',
            'name.unique' => 'El nombre del rol ya existe',
            'description.max' => 'La descripción no debe exceder 500 caracteres',
        ];
    }
}
