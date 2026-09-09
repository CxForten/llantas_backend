<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;
        $categoryId = $this->route('category')?->id;

        return [
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('categories', 'name')
                    ->ignore($categoryId)
                    ->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'icon'       => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active'     => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ];
    }
}
