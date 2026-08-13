<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
  use RefreshDatabase;

  /** @test */
  public function buyer_can_list_their_orders()
  {
    $buyer = User::factory()->create(['role' => 'buyer']);
    $shop = Shop::factory()->create();
    $order = Order::factory()->create([
      'buyer_id' => $buyer->id,
      'shop_id' => $shop->id,
      'status' => OrderStatus::PENDING,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/v1/orders');

    $response->assertStatus(200)
      ->assertJsonCount(1, 'data')
      ->assertJsonFragment([
        'id' => $order->id,
        'status' => OrderStatus::PENDING->value,
      ]);
  }

  /** @test */
  public function buyer_cannot_list_another_buyers_orders()
  {
    $buyer = User::factory()->create(['role' => UserRole::BUYER]);
    $anotherBuyer = User::factory()->create(['role' => UserRole::BUYER]);
    $shop = Shop::factory()->create();
    $order = Order::factory()->create([
      'buyer_id' => $anotherBuyer->id,
      'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/v1/orders');

    $response->assertStatus(200)
      ->assertJsonCount(0, 'data');
  }

  /** @test */
  public function buyer_can_filter_orders_by_status()
  {
    $buyer = User::factory()->create(['role' => 'buyer']);
    $shop = Shop::factory()->create();
    $pendingOrder = Order::factory()->create([
      'buyer_id' => $buyer->id,
      'shop_id' => $shop->id,
      'status' => OrderStatus::PENDING,
    ]);
    $completedOrder = Order::factory()->create([
      'buyer_id' => $buyer->id,
      'shop_id' => $shop->id,
      'status' => OrderStatus::DELIVERED,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/v1/orders?status=' . OrderStatus::PENDING->value);

    $response->assertStatus(200)
      ->assertJsonCount(1, 'data')
      ->assertJsonFragment([
        'id' => $pendingOrder->id,
      ])
      ->assertJsonMissing([
        'id' => $completedOrder->id,
      ]);
  }

  /** @test */
  public function buyer_can_view_order_detail()
  {
    $buyer = User::factory()->create(['role' => 'buyer']);
    $shop = Shop::factory()->create();
    $order = Order::factory()->create([
      'buyer_id' => $buyer->id,
      'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/v1/orders/' . $order->id);

    $response->assertStatus(200)
      ->assertJsonFragment([
        'id' => $order->id,
        'buyer_id' => $buyer->id,
      ]);
  }

  /** @test */
  public function buyer_cannot_view_another_buyers_order_detail()
  {
    $buyer = User::factory()->create(['role' => 'buyer']);
    $anotherBuyer = User::factory()->create(['role' => 'buyer']);
    $shop = Shop::factory()->create();
    $order = Order::factory()->create([
      'buyer_id' => $anotherBuyer->id,
      'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/v1/orders/' . $order->id);

    $response->assertStatus(403);
  }

  /** @test */
  public function seller_can_list_orders_for_their_shop()
  {
    $seller = User::factory()->create(['role' => 'seller']);
    $shop = Shop::factory()->create(['seller_id' => $seller->id]);
    $order = Order::factory()->create([
      'shop_id' => $shop->id,
      'status' => OrderStatus::PENDING,
    ]);

    $response = $this->actingAs($seller)->getJson('/api/v1/seller/orders');

    $response->assertStatus(200)
      ->assertJsonCount(1, 'data')
      ->assertJsonFragment([
        'id' => $order->id,
        'status' => OrderStatus::PENDING->value,
      ]);
  }

  /** @test */
  public function seller_cannot_list_orders_for_another_shop()
  {
    $seller = User::factory()->create(['role' => 'seller']);
    $anotherSeller = User::factory()->create(['role' => 'seller']);
    $shop = Shop::factory()->create(['seller_id' => $anotherSeller->id]);
    $order = Order::factory()->create([
      'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($seller)->getJson('/api/v1/seller/orders');

    $response->assertStatus(200)
      ->assertJsonCount(0, 'data');
  }

  /** @test */
  public function seller_can_filter_orders_by_status()
  {
    $seller = User::factory()->create(['role' => UserRole::SELLER]);
    $shop = Shop::factory()->create(['seller_id' => $seller->id]);
    $pendingOrder = Order::factory()->create([
      'shop_id' => $shop->id,
      'status' => OrderStatus::PENDING,
    ]);
    $completedOrder = Order::factory()->create([
      'shop_id' => $shop->id,
      'status' => OrderStatus::DELIVERED,
    ]);

    $response = $this->actingAs($seller)->getJson('/api/v1/seller/orders?status=' . OrderStatus::PENDING->value);

    $response->assertStatus(200)
      ->assertJsonCount(1, 'data')
      ->assertJsonFragment([
        'id' => $pendingOrder->id,
        'status' => OrderStatus::PENDING->value,
      ]);
  }

  /** @test */
  public function seller_can_view_order_detail()
  {
    $seller = User::factory()->create(['role' => UserRole::SELLER]);
    $shop = Shop::factory()->create(['seller_id' => $seller->id]);
    $order = Order::factory()->create([
      'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($seller)->getJson('/api/v1/seller/orders/' . $order->id);

    $response->assertStatus(200)
      ->assertJsonFragment([
        'id' => $order->id,
        'shop_id' => $shop->id,
      ]);
  }

  /** @test */
  public function seller_cannot_view_order_detail_for_another_shop()
  {
    $seller = User::factory()->create(['role' => UserRole::SELLER]);
    $anotherSeller = User::factory()->create(['role' => UserRole::SELLER]);
    $shop = Shop::factory()->create(['seller_id' => $anotherSeller->id]);
    $order = Order::factory()->create([
      'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($seller)->getJson('/api/v1/seller/orders/' . $order->id);

    $response->assertStatus(403);
  }
}
