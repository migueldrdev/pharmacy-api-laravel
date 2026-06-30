<?php

namespace App\Http\Requests\Batch;

use App\Http\Requests\BaseFormRequest;

class AdjustBatchRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
            'type' => 'required|in:increase,decrease',
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment.required' => 'La cantidad de ajuste es requerida',
            'adjustment.integer' => 'La cantidad debe ser un número entero',
            'adjustment.min' => 'La cantidad debe ser al menos 1',
            'reason.required' => 'El motivo del ajuste es requerido',
            'reason.max' => 'El motivo no debe exceder 500 caracteres',
            'type.required' => 'El tipo de ajuste es requerido',
            'type.in' => 'El tipo debe ser "increase" o "decrease"',
        ];
    }
}
