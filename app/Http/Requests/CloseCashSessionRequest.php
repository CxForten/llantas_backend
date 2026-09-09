<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counted_cents' => ['required', 'integer', 'min:0'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'counted_cents.required' => 'Ingresa el efectivo contado físicamente en la caja.',
        ];
    }
}
