<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'category_id' => $this->category_id,

            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,

            'short_description' => $this->short_description,
            'description' => $this->description,

            'type' => $this->type,

            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'stock' => $this->stock,

            'is_active' => $this->is_active,

            'category' => $this->whenLoaded(
                'category',
                fn () => new CategoryResource($this->category)
            ),

            'primary_image' => $this->whenLoaded(
                'primaryImage',
                fn () => new ProductImageResource($this->primaryImage),
                fn () => $this->relationLoaded('images') && $this->images->isNotEmpty()
                    ? new ProductImageResource($this->images->firstWhere('is_primary', true) ?? $this->images->first())
                    : null
            ),

            'images' => ProductImageResource::collection(
                $this->whenLoaded('images')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
