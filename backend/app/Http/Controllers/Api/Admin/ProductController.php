<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Enums\ProductStatus;
use App\Http\Requests\Product\RejectProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @group Product
 * @tag Product - Product catalog & management (Admin endpoints)
 */
class ProductController extends Controller
{
  /**
   * List products waiting for verification
   *
   * @authenticated
   * @query_param shop_id integer "Filter by shop ID"
   * @query_param category_id integer "Filter by category ID"
   * @query_param search string "Search by product name"
   * @query_param sort string "Sort: newest|oldest" default=newest
   * @response 200 body="{"success":true,"data":[{}],"meta":{"current_page":1,"per_page":15,"total":50,"last_page":4}}"
   */
  public function pending(Request $request): JsonResponse
  {
    $query = Product::where('status', 'pending')
      ->with(['shop.seller', 'category', 'images']);

    // Filter by shop_id
    if ($request->filled('shop_id')) {
      $query->where('shop_id', $request->shop_id);
    }

    // Filter by category_id
    if ($request->filled('category_id')) {
      $query->where('category_id', $request->category_id);
    }

    // Search by product name
    if ($request->filled('search')) {
      $query->where('name', 'like', '%' . $request->search . '%');
    }

    // Sorting
    match ($request->input('sort', 'newest')) {
      'oldest' => $query->orderBy('created_at', 'asc'),
      default => $query->orderBy('created_at', 'desc'),
    };

    $perPage = $request->input('per_page', 15);
    $products = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'data' => ProductResource::collection($products),
      'meta' => [
        'current_page' => $products->currentPage(),
        'per_page' => $products->perPage(),
        'total' => $products->total(),
        'last_page' => $products->lastPage(),
      ],
    ]);
  }

  /**
   * Approve a pending product
   *
   * @authenticated
   * @response 200 body="{"success":true,"message":"Produk berhasil disetujui.","data":{}}"
   * @response 422 body="{"success":false,"message":"Produk tidak dalam status pending."}"
   */
  public function approve(Product $product): JsonResponse
  {
    if ($product->status !== ProductStatus::PENDING) {
      return response()->json([
        'success' => false,
        'message' => 'Produk tidak dalam status pending.',
      ], 422);
    }

    $product->update([
      'status' => ProductStatus::APPROVED,
      'verified_at' => now(),
    ]);

    // Clear public product cache
    Cache::forget("public_product_{$product->slug}");
    Cache::forget('public_products_*');

    return response()->json([
      'success' => true,
      'message' => 'Produk berhasil disetujui.',
      'data' => new ProductResource($product->load(['shop.seller', 'category', 'images'])),
    ]);
  }

  /**
   * Reject a pending product
   *
   * @authenticated
   * @requestBody required
   * @bodyParam rejection_reason string required "Reason for rejection" example=Produk tidak sesuai kategori
   * @response 200 body="{"success":true,"message":"Produk berhasil ditolak.","data":{}}"
   * @response 422 body="{"success":false,"message":"Produk tidak dalam status pending."}"
   */
  public function reject(Product $product, RejectProductRequest $request): JsonResponse
  {
    if ($product->status !== ProductStatus::PENDING) {
      return response()->json([
        'success' => false,
        'message' => 'Produk tidak dalam status pending.',
      ], 422);
    }

    $product->update([
      'status' => ProductStatus::REJECTED,
      'rejection_reason' => $request->validated('rejection_reason'),
    ]);

    // Clear public product cache
    Cache::forget("public_product_{$product->slug}");
    Cache::forget('public_products_*');

    return response()->json([
      'success' => true,
      'message' => 'Produk berhasil ditolak.',
      'data' => new ProductResource($product->load(['shop.seller', 'category', 'images'])),
    ]);
  }
}
