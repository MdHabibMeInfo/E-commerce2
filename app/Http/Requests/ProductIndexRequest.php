<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'type' => [
                'nullable',
                'string',
                Rule::in([
                    'simple',
                    'variable',
                ]),
            ],

            'min_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:min_price',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'id',
                    'name',
                    'price',
                    'stock',
                    'created_at',
                ]),
            ],

            'direction' => [
                'nullable',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
