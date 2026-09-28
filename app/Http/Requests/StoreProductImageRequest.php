<?php

namespace App\Http\Requests;

use App\Models\ProductImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product
            && $this->user()?->can('update', $product)
            && $this->user()?->can(
                'create',
                ProductImage::class
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
                'required_without:images',
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,avif',
                'max:5120',
            ],

            'images' => [
                'required_without:image',
                'nullable',
                'array',
                'min:1',
            ],

            'images.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,avif',
                'max:5120',
            ],

            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],

            'alt_texts' => [
                'nullable',
                'array',
            ],

            'alt_texts.*' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_primary' => [
                'sometimes',
                'boolean',
            ],

            'primary_index' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}
