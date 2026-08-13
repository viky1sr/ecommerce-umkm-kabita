<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'slug' => $this->slug,
      'icon' => $this->icon,
      'description' => $this->description,
      'product_count' => $this->when($request->routeIs('categories.show'), $this->relationLoaded('products') ? $this->products->count() : $this->products()->where('status', 'approved')->count()),
      'products' => ProductResource::collection($this->whenLoaded('products')),
      'created_at' => $this->created_at?->toDateTimeString(),
    ];
  }
}
