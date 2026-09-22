<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductVariant::class) ?? false;
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
                'unique:product_variants,sku',
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
