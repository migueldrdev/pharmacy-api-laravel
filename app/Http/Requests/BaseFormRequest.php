<?php

namespace App\Http\Requests;

use App\Helpers\ResponseHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        $firstField = collect($validator->errors()->keys())->first();
        $title = $firstField ? "Error en el campo: $firstField" : 'Error de validación';
        $message = collect($validator->errors()->all())->first() ?: 'Datos inválidos';

        throw new HttpResponseException(
            ResponseHelper::error(
                message: $message,
                code: 422,
                extra: ['errors' => $validator->errors()],
                title: $title
            )
        );
    }
}
