<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $variant = $this->route('variant');

        return $this->user()?->can('update', $variant) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')
                    ->ignore($this->route('variant')),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'compare_at_price' => [
                'nullable',
                'numeric',
                'gt:price',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'attribute_value_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'attribute_value_ids.*' => [
                'integer',
                'distinct',
                'exists:attribute_values,id',
            ],
        ];
    }
}
