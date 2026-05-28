<?php

namespace App\Http\Requests\Purchase;

use App\Http\Requests\BaseFormRequest;

class StorePurchaseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_date' => ['required', 'date'],
            'total' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_document_type_id' => ['nullable', 'exists:purchase_document_types,id'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'active' => ['boolean'],
            // Validaciones para los detalles de compra
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'exists:products,id'],
            'details.*.quantity' => ['required', 'integer', 'min:1'],
            'details.*.price' => ['required', 'numeric', 'min:0'],
            'details.*.subtotal' => ['required', 'numeric', 'min:0'],
            'details.*.batch_number' => ['nullable', 'string', 'max:255'],
            'details.*.expiration_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_date.required' => 'La fecha de compra es obligatoria.',
            'total.required' => 'El total de la compra es obligatorio.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
            'purchase_document_type_id.exists' => 'El tipo de documento de compra seleccionado no existe.',
            'details.required' => 'Debe haber al menos un detalle de compra.',
            'details.array' => 'Los detalles de compra deben ser un array.',
            'details.*.product_id.required' => 'El producto es obligatorio en cada detalle.',
            'details.*.product_id.exists' => 'El producto seleccionado en un detalle no existe.',
            'details.*.quantity.required' => 'La cantidad es obligatoria en cada detalle.',
            'details.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'details.*.price.required' => 'El precio es obligatorio en cada detalle.',
            'details.*.subtotal.required' => 'El subtotal es obligatorio en cada detalle.',
        ];
    }
}
