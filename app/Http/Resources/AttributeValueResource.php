<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueResource extends JsonResource
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

            'product_attribute_id' => $this->product_attribute_id,

            'value' => $this->value,

            'slug' => $this->slug,

            'sort_order' => $this->sort_order,

            'is_active' => $this->is_active,

            'product_attribute' => $this->whenLoaded(
                'productAttribute',
                fn () => new ProductAttributeResource(
                    $this->productAttribute
                )
            ),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}
