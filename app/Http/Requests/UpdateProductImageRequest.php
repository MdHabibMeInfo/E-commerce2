<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');
        $productImage = $this->route('productImage');

        return $product
            && $productImage
            && $this->user()?->can(
                'update',
                $product
            )
            && $this->user()?->can(
                'update',
                $productImage
            );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'sometimes',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],

            'alt_text' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            'is_primary' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
