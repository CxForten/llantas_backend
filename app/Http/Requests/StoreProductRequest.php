<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;

        return [
            'sku' => [
                'required', 'string', 'max:40',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('business_id', $businessId))
                    ->whereNull('deleted_at'),
            ],
            'name'        => ['required', 'string', 'max:180'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand'       => ['nullable', 'string', 'max:80'],
            'spec'        => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],

            'cost_cents'  => ['required', 'integer', 'min:1'],
            'margin_pct'  => ['nullable', 'integer', 'min:0', 'max:200'],

            'stock'       => ['nullable', 'integer', 'min:0'],
            'min_stock'   => ['nullable', 'integer', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'iva_rate'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'active'      => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required'        => 'El SKU es obligatorio.',
            'sku.unique'          => 'Ya existe otro producto con ese SKU.',
            'name.required'       => 'El nombre del producto es obligatorio.',
            'cost_cents.required' => 'Ingresa el precio de costo del producto.',
            'cost_cents.min'      => 'El precio de costo debe ser mayor a cero.',
        ];
    }
}
