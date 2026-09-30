<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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

            'parent_id' => $this->parent_id,

            'name' => $this->name,

            'slug' => $this->slug,

            'description' => $this->description,

            'image' => $this->image,

            'image_url' => $this->image_url,

            'is_active' => $this->is_active,

            'sort_order' => $this->sort_order,

            'parent' => $this->whenLoaded(
                'parent',
                fn () => new CategoryResource($this->parent)
            ),
        ];
    }
}
