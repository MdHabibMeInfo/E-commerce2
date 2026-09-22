<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignProductAttributesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $this->user()?->can('update', $product) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_attribute_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'product_attribute_ids.*' => [
                'integer',
                'distinct',
                'exists:product_attributes,id',
            ],
        ];
    }
}
