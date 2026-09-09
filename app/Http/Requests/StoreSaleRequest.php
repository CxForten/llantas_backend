<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty'        => ['required', 'integer', 'min:1', 'max:9999'],

            'margin_pct'     => ['nullable', 'integer', 'min:0', 'max:200'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:efectivo,tarjeta,transferencia'],
            'doc_type'       => ['required', 'in:consumidor_final,factura'],
            'received_cents' => ['nullable', 'integer', 'min:0'],

            'override_total_cents' => ['nullable', 'integer', 'min:0'],
            'override_reason'      => ['nullable', 'string', 'max:255', 'required_with:override_total_cents'],

            'customer'       => ['nullable', 'array'],
            'customer.name'  => ['required_if:doc_type,factura', 'nullable', 'string', 'max:180'],
            'customer.ident' => ['required_if:doc_type,factura', 'nullable', 'string', 'regex:/^\d{10}$|^\d{13}$/'],
            'customer.email' => ['required_if:doc_type,factura', 'nullable', 'email', 'max:180'],

            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                => 'El carrito está vacío.',
            'items.*.qty.min'               => 'La cantidad debe ser al menos 1.',
            'override_reason.required_with' => 'Debes escribir el motivo del cambio de total.',
            'customer.name.required_if'     => 'Escribe el nombre del cliente para la factura.',
            'customer.ident.required_if'    => 'Ingresa la cédula o RUC del cliente.',
            'customer.ident.regex'          => 'Ingresa una cédula (10 dígitos) o RUC (13 dígitos) válido.',
            'customer.email.required_if'    => 'Ingresa un correo válido para enviar la factura.',
            'payment_method.in'             => 'El método de pago no es válido.',
        ];
    }
}
