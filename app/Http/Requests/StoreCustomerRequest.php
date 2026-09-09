<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ident_type' => ['required', 'in:cedula,ruc,pasaporte,consumidor_final'],
            'ident'      => ['nullable', 'string', 'max:20', 'required_unless:ident_type,consumidor_final'],
            'name'       => ['required', 'string', 'max:180'],
            'email'      => ['nullable', 'email', 'max:180'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type  = $this->input('ident_type');
            $ident = (string) $this->input('ident');

            if ($type === 'cedula' && ! preg_match('/^\d{10}$/', $ident)) {
                $validator->errors()->add('ident', 'La cédula debe tener 10 dígitos.');
            }

            if ($type === 'ruc' && ! preg_match('/^\d{13}$/', $ident)) {
                $validator->errors()->add('ident', 'El RUC debe tener 13 dígitos.');
            }
        });
    }
}
