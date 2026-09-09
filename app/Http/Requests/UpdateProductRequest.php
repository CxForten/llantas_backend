<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;
        $productId  = $this->route('product')->id;

        return [
            'sku' => [
                'sometimes', 'required', 'string', 'max:40',
                Rule::unique('products', 'sku')
                    ->ignore($productId)
                    ->where(fn ($q) => $q->where('business_id', $businessId))
                    ->whereNull('deleted_at'),
            ],
            'name'        => ['sometimes', 'required', 'string', 'max:180'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'brand'       => ['sometimes', 'nullable', 'string', 'max:80'],
            'spec'        => ['sometimes', 'nullable', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],

            'cost_cents'  => ['sometimes', 'required', 'integer', 'min:1'],
            'margin_pct'  => ['sometimes', 'integer', 'min:0', 'max:200'],

            'min_stock'   => ['sometimes', 'integer', 'min:0'],
            'track_stock' => ['sometimes', 'boolean'],
            'iva_rate'    => ['sometimes', 'integer', 'min:0', 'max:100'],
            'active'      => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique'     => 'Ya existe otro producto con ese SKU.',
            'cost_cents.min' => 'El precio de costo debe ser mayor a cero.',
        ];
    }
}
