<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product_id' => $this->product_id,

            'name' => $this->name,

            'sku' => $this->sku,

            'price' => $this->price,

            'compare_at_price' => $this->compare_at_price,

            'stock' => $this->stock,

            'is_active' => $this->is_active,

            'attributes' => $this->whenLoaded(
                'attributeValues',
                fn() => $this->attributeValues->map(
                    fn($attributeValue) => [
                        'attribute' => $attributeValue->productAttribute->name,
                        'attribute_slug' => $attributeValue->productAttribute->slug,
                        'value' => $attributeValue->value,
                        'value_slug' => $attributeValue->slug,
                    ]
                )->values()
            ),
        ];
    }
}
