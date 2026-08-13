<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
  use RefreshDatabase;

  private function actingAsBuyer(): User
  {
    $buyer = User::factory()->create(['role' => UserRole::BUYER]);
    Cart::factory()->create(['buyer_id' => $buyer->id]);

    return $buyer;
  }

  /** @test */
  public function buyer_can_view_empty_cart() : void
  {
    $buyer = $this->actingAsBuyer();

    $response = $this->actingAs($buyer)->getJson('/api/v1/cart');

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'data' => [
          'id' => $buyer->cart->id,
          'buyer_id' => $buyer->id,
          'groups_by_shop' => [],
          'stock_status' => [
            'available' => true,
            'unavailable_items' => [],
          ],
        ],
      ])
      ->assertJsonStructure([
        'success',
        'data' => [
          'id',
          'buyer_id',
          'groups_by_shop',
          'subtotal',
          'total',
          'total_items',
          'stock_status',
          'created_at',
          'updated_at',
        ],
      ]);
  }

  /** @test */
  public function buyer_can_add_item_to_cart() : void
  {
    $buyer = $this->actingAsBuyer();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
      'shop_id' => $shop->id,
      'price' => 15000,
      'stock' => 10,
      'status' => ProductStatus::APPROVED,
    ]);

    $response = $this->actingAs($buyer)->postJson('/api/v1/cart/items', [
      'product_id' => $product->id,
      'quantity' => 2,
    ]);

    $response->assertStatus(201)
      ->assertJson([
        'success' => true,
        'message' => 'Barang berhasil ditambahkan ke keranjang.',
      ])
      ->assertJsonStructure([
        'success',
        'message',
        'data' => [
          'id',
          'buyer_id',
          'groups_by_shop' => [
            '*' => [
              'shop' => [
                'id',
                'name',
                'slug',
                'logo',
              ],
              'items' => [
                '*' => [
                  'id',
                  'cart_id',
                  'product_id',
                  'quantity',
                  'product',
                  'subtotal',
                  'created_at',
                  'updated_at',
                ],
              ],
              'subtotal',
            ],
          ],
          'subtotal',
          'total',
          'total_items',
          'stock_status',
          'created_at',
          'updated_at',
        ],
      ]);

    $this->assertDatabaseCount('cart_items', 1);
    $this->assertEquals(2, $buyer->cart->fresh()->items->first()->quantity);
  }

  /** @test */
  public function buyer_can_update_cart_item_quantity() : void
  {
    $buyer = $this->actingAsBuyer();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
      'shop_id' => $shop->id,
      'price' => 20000,
      'stock' => 10,
      'status' => ProductStatus::APPROVED,
    ]);
    $cartItem = CartItem::factory()->create([
      'cart_id' => $buyer->cart->id,
      'product_id' => $product->id,
      'quantity' => 1,
    ]);

    $response = $this->actingAs($buyer)->putJson('/api/v1/cart/items/' . $cartItem->id, [
      'quantity' => 3,
    ]);

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'message' => 'Jumlah barang berhasil diperbarui.',
      ])
      ->assertJsonPath('data.groups_by_shop.0.items.0.quantity', 3);

    $this->assertEquals(3, $cartItem->fresh()->quantity);
  }

  /** @test */
  public function buyer_can_remove_cart_item() : void
  {
    $buyer = $this->actingAsBuyer();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
      'shop_id' => $shop->id,
      'price' => 10000,
      'stock' => 10,
      'status' => ProductStatus::APPROVED,
    ]);
    $cartItem = CartItem::factory()->create([
      'cart_id' => $buyer->cart->id,
      'product_id' => $product->id,
      'quantity' => 1,
    ]);

    $response = $this->actingAs($buyer)->deleteJson('/api/v1/cart/items/' . $cartItem->id);

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'message' => 'Barang berhasil dihapus dari keranjang.',
      ])
      ->assertJsonPath('data.groups_by_shop', []);

    $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
  }

  /** @test */
  public function buyer_can_clear_cart() : void
  {
    $buyer = $this->actingAsBuyer();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
      'shop_id' => $shop->id,
      'price' => 12000,
      'stock' => 10,
      'status' => ProductStatus::APPROVED,
    ]);
    CartItem::factory()->create([
      'cart_id' => $buyer->cart->id,
      'product_id' => $product->id,
      'quantity' => 2,
    ]);

    $response = $this->actingAs($buyer)->deleteJson('/api/v1/cart/clear');

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'message' => 'Keranjang belanja berhasil dikosongkan.',
      ])
      ->assertJsonPath('data.groups_by_shop', []);

    $this->assertDatabaseCount('cart_items', 0);
  }

  /** @test */
  public function buyer_can_validate_cart_for_checkout() : void
  {
    $buyer = $this->actingAsBuyer();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create([
      'shop_id' => $shop->id,
      'price' => 25000,
      'stock' => 5,
      'status' => ProductStatus::APPROVED,
    ]);
    CartItem::factory()->create([
      'cart_id' => $buyer->cart->id,
      'product_id' => $product->id,
      'quantity' => 2,
    ]);

    $response = $this->actingAs($buyer)->getJson('/api/v1/cart/validate');

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'data' => [
          'available' => true,
          'unavailable_items' => [],
          'subtotal' => 50000.0,
          'total' => 50000.0,
          'total_items' => 2,
        ],
      ])
      ->assertJsonStructure([
        'success',
        'data' => [
          'available',
          'unavailable_items',
          'groups_by_shop' => [
            '*' => [
              'shop' => [
                'id',
                'name',
                'slug',
                'logo',
              ],
              'items' => [
                '*' => [
                  'id',
                  'cart_id',
                  'product_id',
                  'quantity',
                  'product',
                  'subtotal',
                  'created_at',
                  'updated_at',
                ],
              ],
              'subtotal',
            ],
          ],
          'subtotal',
          'total',
          'total_items',
        ],
      ]);
  }
}
