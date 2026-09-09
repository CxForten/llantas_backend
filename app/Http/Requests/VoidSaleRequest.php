<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'        => ['required', 'string', 'min:5', 'max:255'],
            'restore_stock' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Escribe el motivo de la anulación.',
            'reason.min'      => 'El motivo debe explicar qué pasó (mínimo 5 caracteres).',
        ];
    }
}
