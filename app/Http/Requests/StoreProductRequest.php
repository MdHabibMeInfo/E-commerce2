<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,slug',
            ],

            'sku' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,sku',
            ],

            'short_description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'type' => [
                'required',
                'string',
                Rule::in([
                    'simple',
                    'variable',
                ]),
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'compare_at_price' => [
                'nullable',
                'numeric',
                'min:0',
                'gt:price',
            ],

            'stock' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,avif',
                'max:5120',
            ],

            'images' => [
                'nullable',
                'array',
            ],

            'images.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,avif',
                'max:5120',
            ],

            'primary_index' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}
