<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ShopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\RejectShopRequest;
use App\Http\Resources\ShopResource;
use App\Mail\ShopVerifiedMail;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Shop
 * @tag Shop - Shop management for sellers (Admin endpoints)
 */
class ShopController extends Controller
{
  /**
   * List shops awaiting verification
   *
   * @authenticated
   * @query_param search string "Search by shop name"
   * @query_param sort string "Sort: newest|oldest" default=newest
   * @query_param per_page integer "Items per page" default=15
   * @response 200 body="{"success":true,"data":[{}],"meta":{"current_page":1,"per_page":15,"total":50,"last_page":4}}"
   */
  public function pending(Request $request): JsonResponse
  {
    $query = Shop::where('status', ShopStatus::PENDING)
      ->with(['seller']);

    // Search by shop name
    if ($request->filled('search')) {
      $query->where('name', 'like', '%' . $request->search . '%');
    }

    // Sorting
    match ($request->input('sort', 'newest')) {
      'oldest' => $query->orderBy('created_at', 'asc'),
      default => $query->orderBy('created_at', 'desc'),
    };

    $perPage = $request->input('per_page', 15);
    $shops = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'data' => ShopResource::collection($shops),
      'meta' => [
        'current_page' => $shops->currentPage(),
        'per_page' => $shops->perPage(),
        'total' => $shops->total(),
        'last_page' => $shops->lastPage(),
      ],
    ]);
  }

  /**
   * Verify a pending shop (Admin)
   * @authenticated
   * @response 200 body="{"success":true,"message":"Toko berhasil diverifikasi.","data":{}}"
   * @response 422 body="{"success":false,"message":"Toko tidak dalam status pending."}"
   */
  public function verify(Shop $shop): JsonResponse
  {
    if ($shop->status !== ShopStatus::PENDING) {
      return response()->json([
        'success' => false,
        'message' => 'Toko tidak dalam status pending.',
      ], 422);
    }

    $admin = Auth::user();

    $shop = DB::transaction(function () use ($shop, $admin): Shop {
      $shop->update([
        'status' => ShopStatus::VERIFIED,
        'verified_by' => $admin->id,
        'verified_at' => now(),
      ]);

      // Queue notification in the same transaction. If the queue table or
      // connection is unavailable, the verification is rolled back and can
      // safely be retried instead of leaving a misleading partial success.
      $shop->seller->notify(new ShopVerifiedMail(
        $shop->seller->name,
        $shop->name,
        $admin->name
      ));

      return $shop->fresh(['seller', 'verifier']);
    });

    return response()->json([
      'success' => true,
      'message' => 'Toko berhasil diverifikasi.',
      'data' => new ShopResource($shop),
    ]);
  }

  /**
   * Reject a pending shop (Admin)
   * @authenticated
   * @requestBody required
   * @bodyParam rejection_reason string required "Reason for rejection" example=Informasi toko tidak lengkap
   * @response 200 body="{"success":true,"message":"Toko berhasil ditolak."}"
   * @response 422 body="{"success":false,"message":"Toko tidak dalam status pending."}"
   */
  public function reject(Shop $shop, RejectShopRequest $request): JsonResponse
  {
    if ($shop->status !== ShopStatus::PENDING) {
      return response()->json([
        'success' => false,
        'message' => 'Toko tidak dalam status pending.',
      ], 422);
    }

    $shop->update([
      'status' => ShopStatus::REJECTED,
      'rejection_reason' => $request->validated('rejection_reason'),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Toko berhasil ditolak.',
      'data' => new ShopResource($shop->load('seller')),
    ]);
  }
}
