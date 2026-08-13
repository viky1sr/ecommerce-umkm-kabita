<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingControllerTest extends TestCase
{
  use RefreshDatabase;

  private User $buyer;

  protected function setUp(): void
  {
    parent::setUp();
    $this->buyer = User::factory()->create(['role' => 'buyer']);
  }

  /**
   * Test GET /api/shipping/options returns valid structure
   */
  public function test_get_shipping_options_returns_valid_structure(): void
  {
    $response = $this->actingAs($this->buyer)->getJson('/api/v1/shipping/options');

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'data' => [
          [
            'id' => 'cod',
            'name' => 'Cash On Delivery (COD)',
            'cost' => 0,
            'estimated_days' => null,
          ],
          [
            'id' => 'kurir_reguler',
            'name' => 'Kurir Reguler (3-5 Hari)',
            'base_cost' => 10000,
            'estimated_days' => '3-5 hari kerja',
          ],
          [
            'id' => 'kurir_express',
            'name' => 'Kurir Express (1-2 Hari)',
            'base_cost' => 20000,
            'estimated_days' => '1-2 hari kerja',
          ],
        ],
      ]);
  }

  /**
   * Test GET /api/shipping/options requires authentication
   */
  public function test_get_shipping_options_requires_authentication(): void
  {
    $response = $this->getJson('/api/v1/shipping/options');

    $response->assertStatus(401);
  }

  /**
   * Test POST /api/shipping/calculate with COD method
   */
  public function test_calculate_shipping_cost_for_cod(): void
  {
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', [
        'weight' => 500,
        'shipping_method' => 'cod',
      ]);

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'data' => [
          'shipping_method' => 'cod',
          'estimated_cost' => 0,
          'estimated_days' => null,
        ],
      ]);
  }

  /**
   * Test POST /api/shipping/calculate with courier reguler
   */
  public function test_calculate_shipping_cost_for_courier_reguler(): void
  {
    // Weight: 2000 grams = 2 kg
    // Cost: base_rate(10000) + (2 * 5000) = 20000
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', [
        'weight' => 2000,
        'shipping_method' => 'kurir',
        'courier_type' => 'reguler',
      ]);

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'data' => [
          'shipping_method' => 'kurir',
          'courier_type' => 'reguler',
          'estimated_cost' => 20000,
          'estimated_days' => '3-5 hari kerja',
        ],
      ]);
  }

  /**
   * Test POST /api/shipping/calculate with courier express
   */
  public function test_calculate_shipping_cost_for_courier_express(): void
  {
    // Weight: 1000 grams = 1 kg
    // Cost: base_rate(20000) + (1 * 5000) = 25000
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', [
        'weight' => 1000,
        'shipping_method' => 'kurir',
        'courier_type' => 'express',
      ]);

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'data' => [
          'shipping_method' => 'kurir',
          'courier_type' => 'express',
          'estimated_cost' => 25000,
          'estimated_days' => '1-2 hari kerja',
        ],
      ]);
  }

  /**
   * Test POST /api/shipping/calculate defaults to reguler when courier_type not provided for kurir
   */
  public function test_calculate_shipping_defaults_to_reguler(): void
  {
    // Weight: 1000 grams = 1 kg
    // Cost: base_rate(10000) + (1 * 5000) = 15000
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', [
        'weight' => 1000,
        'shipping_method' => 'kurir',
        'courier_type' => 'reguler',
      ]);

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'data' => [
          'shipping_method' => 'kurir',
          'courier_type' => 'reguler',
          'estimated_cost' => 15000,
          'estimated_days' => '3-5 hari kerja',
        ],
      ]);
  }

  /**
   * Test POST /api/shipping/calculate requires authentication
   */
  public function test_calculate_shipping_requires_authentication(): void
  {
    $response = $this->postJson('/api/v1/shipping/calculate', [
      'weight' => 1000,
      'shipping_method' => 'kurir',
    ]);

    $response->assertStatus(401);
  }

  /**
   * Test POST /api/shipping/calculate validates required fields
   */
  public function test_calculate_shipping_validates_required_fields(): void
  {
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', []);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['weight', 'shipping_method']);
  }

  /**
   * Test POST /api/shipping/calculate validates shipping_method value
   */
  public function test_calculate_shipping_validates_shipping_method(): void
  {
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', [
        'weight' => 1000,
        'shipping_method' => 'invalid',
      ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['shipping_method']);
  }

  /**
   * Test POST /api/shipping/calculate validates weight is numeric and positive
   */
  public function test_calculate_shipping_validates_weight(): void
  {
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', [
        'weight' => -100,
        'shipping_method' => 'kurir',
      ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['weight']);
  }

  /**
   * Test POST /api/shipping/calculate validates courier_type when shipping_method is kurir
   */
  public function test_calculate_shipping_validates_courier_type_for_kurir(): void
  {
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', [
        'weight' => 1000,
        'shipping_method' => 'kurir',
        'courier_type' => 'invalid',
      ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['courier_type']);
  }

  /**
   * Test POST /api/shipping/calculate allows null courier_type for COD
   */
  public function test_calculate_shipping_allows_null_courier_type_for_cod(): void
  {
    $response = $this->actingAs($this->buyer)
      ->postJson('/api/v1/shipping/calculate', [
        'weight' => 1000,
        'shipping_method' => 'cod',
        'courier_type' => null,
      ]);

    $response->assertStatus(200);
  }
}
