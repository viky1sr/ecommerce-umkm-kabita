<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\CartCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $service = app(CartCalculationService::class);
    $cart = $this->resource;

    $groups = collect($service->groupItemsByShop($cart))
      ->map(fn(array $group) => new ShopCartGroupResource((object) $group))
      ->all();

    return [
      'id' => $this->id,
      'buyer_id' => $this->buyer_id,
      'groups_by_shop' => $groups,
      'subtotal' => $this->whenLoaded('items', fn() => $service->calculateSubtotal($cart)),
      // Cart totals exclude shipping; shipping is selected during checkout.
      'total' => $this->whenLoaded('items', fn() => $service->calculateSubtotal($cart)),
      'total_items' => $this->whenLoaded('items', fn() => $this->items->sum('quantity')),
      'stock_status' => $this->when(true, fn() => $service->checkStockAvailability($cart)),
      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
  }
}
