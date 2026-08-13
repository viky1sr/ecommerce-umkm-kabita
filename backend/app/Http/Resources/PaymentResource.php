<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array<string, mixed>
   */
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'order_id' => $this->order_id,
      'amount' => $this->amount,
      'status' => $this->status,
      'proof_image' => $this->proof_image ? asset('storage/' . $this->proof_image) : null,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'order' => $this->whenLoaded('order', function () {
        return [
          'order_number' => $this->order->order_number,
          'buyer' => $this->order->relationLoaded('buyer') ? new UserResource($this->order->buyer) : null,
          'shop' => $this->order->relationLoaded('shop') ? new ShopResource($this->order->shop) : null,
        ];
      }),
    ];
  }
}
