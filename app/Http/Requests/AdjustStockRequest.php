<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type'       => ['required', 'in:entrada,salida,ajuste'],
            'qty'        => ['required', 'integer', 'min:0', 'max:999999'],
            'reason'     => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'         => 'El tipo de ajuste debe ser entrada, salida o ajuste.',
            'qty.required'    => 'Ingresa una cantidad válida.',
            'reason.required' => 'Escribe el motivo del movimiento.',
            'reason.min'      => 'El motivo es muy corto. Ej: compra a distribuidora, conteo físico.',
        ];
    }
}
