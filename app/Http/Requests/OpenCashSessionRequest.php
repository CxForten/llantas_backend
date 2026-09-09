<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opening_cents' => ['required', 'integer', 'min:0'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'opening_cents.required' => 'Ingresa el fondo inicial de caja (puede ser 0).',
        ];
    }
}
