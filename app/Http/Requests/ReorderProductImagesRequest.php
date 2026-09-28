<?php

namespace App\Http\Requests;

use App\Models\ProductImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderProductImagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product
            && $this->user()?->can('update', $product)
            && $this->user()?->can('update', ProductImage::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'image_ids.*' => [
                'required',
                'integer',
                'exists:product_images,id',
            ],
        ];
    }
}
