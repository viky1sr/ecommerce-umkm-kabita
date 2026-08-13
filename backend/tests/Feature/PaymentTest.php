<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentTest extends TestCase
{
  use RefreshDatabase;

  private User $buyer;
  private User $admin;
  private Order $order;
  private Payment $payment;

  protected function setUp(): void
  {
    parent::setUp();

    $this->buyer = User::factory()->create(['role' => 'buyer', 'status' => 'active']);
    $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

    $this->order = Order::factory()->create([
      'buyer_id' => $this->buyer->id,
      'status' => OrderStatus::PENDING,
    ]);

    $this->payment = Payment::factory()->create([
      'order_id' => $this->order->id,
      'amount' => 100000,
      'status' => PaymentStatus::PENDING,
    ]);
  }

  // =====================================================
  // 5.1 Payment Upload & Verification
  // =====================================================

  public function test_buyer_can_upload_payment_proof(): void
  {
    $file = \Illuminate\Http\UploadedFile::fake()->create('receipt.jpg', 1024);

    $response = $this->actingAs($this->buyer)
      ->post("/api/v1/payments/{$this->payment->id}/upload", [
        'proof_image' => $file,
      ]);

    $response->assertStatus(200)
      ->assertJson([
        'success' => true,
        'message' => 'Bukti pembayaran berhasil diunggah.',
      ]);

    $this->assertDatabaseHas('payments', [
      'id' => $this->payment->id,
      'status' => 'pending',
    ]);
  }

  public function test_buyer_cannot_upload_for_other_users_payment(): void
  {
    $otherBuyer = User::factory()->create(['role' => 'buyer']);
    $otherOrder = Order::factory()->create(['buyer_id' => $otherBuyer->id]);
    $otherPayment = Payment::factory()->create(['order_id' => $otherOrder->id, 'status' => PaymentStatus::PENDING]);

    $file = \Illuminate\Http\UploadedFile::fake()->create('receipt.jpg');

    $response = $this->actingAs($this->buyer)
      ->post("/api/v1/payments/{$otherPayment->id}/upload", [
        'proof_image' => $file,
      ]);

    $response->assertStatus(403);
  }

  public function test_admin_can_list_pending_payments(): void
  {
    $response = $this->actingAs($this->admin)
      ->get('/api/v1/payments/pending');

    $response->assertStatus(200)
      ->assertJsonPath('data.0.id', $this->payment->id);
  }

  public function test_admin_can_verify_payment(): void
  {
    Notification::fake();

    $response = $this->actingAs($this->admin)
      ->patch("/api/v1/payments/{$this->payment->id}/verify");

    $response->assertStatus(200)
      ->assertJsonPath('data.status', 'processing');

    $this->assertDatabaseHas('payments', [
      'id' => $this->payment->id,
      'status' => 'verified',
    ]);

    $this->assertDatabaseHas('orders', [
      'id' => $this->order->id,
      'status' => 'processing',
    ]);

    Notification::assertSentTo(
      [$this->order->shop->seller],
      \App\Mail\PaymentVerifiedMail::class
    );
  }

  public function test_admin_can_reject_payment(): void
  {
    Notification::fake();

    $response = $this->actingAs($this->admin)
      ->patch("/api/v1/payments/{$this->payment->id}/reject", [
        'rejection_reason' => 'Bukti tidak jelas',
      ]);

    $response->assertStatus(200)
      ->assertJsonPath('data.status', 'rejected');

    $this->assertDatabaseHas('payments', [
      'id' => $this->payment->id,
      'status' => 'rejected',
    ]);

    Notification::assertSentTo(
      [$this->payment->order->buyer],
      \App\Mail\PaymentRejectedMail::class
    );
  }

  // =====================================================
  // 5.2 COD Payment Handling
  // =====================================================

  public function test_buyer_can_confirm_cod_payment(): void
  {
    $codOrder = Order::factory()->create([
      'buyer_id' => $this->buyer->id,
      'payment_method' => 'cod',
      'status' => OrderStatus::PENDING,
    ]);

    $codPayment = Payment::factory()->create([
      'order_id' => $codOrder->id,
      'status' => PaymentStatus::PENDING,
    ]);

    $response = $this->actingAs($this->buyer)
      ->post("/api/v1/orders/{$codOrder->id}/cod-confirm");

    $response->assertStatus(200)
      ->assertJsonPath('data.status', 'processing');

    $this->assertDatabaseHas('payments', [
      'id' => $codPayment->id,
      'status' => 'verified',
    ]);
  }

  public function test_buyer_cannot_confirm_cod_for_non_cod_order(): void
  {
    // Create a non-COD order explicitly
    $nonCodOrder = Order::factory()->create([
      'buyer_id' => $this->buyer->id,
      'payment_method' => 'transfer',
    ]);

    $response = $this->actingAs($this->buyer)
      ->post("/api/v1/orders/{$nonCodOrder->id}/cod-confirm");

    $response->assertStatus(422)
      ->assertJsonPath('message', 'Hanya order dengan metode pembayaran COD yang dapat dikonfirmasi.');
  }
}
