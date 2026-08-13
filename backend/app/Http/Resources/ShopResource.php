<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use App\Http\Resources\ProductResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'address' => $this->address,
            'logo' => $this->logo ? asset('storage/' . $this->logo) : null,
            'banner' => $this->banner ? asset('storage/' . $this->banner) : null,
            'status' => $this->status,
            'seller' => $this->when($request->routeIs('shops.my-shop'), new UserResource($this->whenLoaded('seller'))),
            'product_count' => $this->when($request->routeIs('shops.my-shop'), $this->products()->count()),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'verifier' => $this->whenLoaded('verifier', fn() => new UserResource($this->verifier)),
            'verified_at' => $this->verified_at ? Carbon::parse($this->verified_at)->toDateTimeString() : null,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toDateTimeString() : null,
        ];
    }
}
