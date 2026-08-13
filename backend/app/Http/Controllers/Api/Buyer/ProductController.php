<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @group Product
 * @tag Product - Product catalog & management (Public endpoints)
 */
class ProductController extends Controller
{
  /**
   * @unauthenticated
   * Browse public product catalog with filters
   *
   * @queryParam search string "Search by product name" example=sepatu
   * @queryParam category_id integer "Filter by category ID" example=1
   * @queryParam shop_id integer "Filter by shop ID" example=2
   * @queryParam min_price integer "Minimum price filter" example=10000
   * @queryParam max_price integer "Maximum price filter" example=500000
   * @query_param sort string "Sort: newest|price_asc|price_desc" default=newest example=newest
   * @query_param per_page integer "Items per page" default=12 example=12
   * @response 200 body="{"success":true,"data":[{}],"meta":{"current_page":1,"per_page":12,"total":100,"last_page":9}}"
   */
  public function index(Request $request): JsonResponse
  {
    $cacheKey = 'public_products_' . md5(json_encode([
      'search' => $request->input('search'),
      'category_id' => $request->input('category_id'),
      'shop_id' => $request->input('shop_id'),
      'min_price' => $request->input('min_price'),
      'max_price' => $request->input('max_price'),
      'sort' => $request->input('sort', 'newest'),
      'per_page' => $request->input('per_page', 12),
      'page' => $request->input('page', 1),
    ]));

    $products = Cache::remember($cacheKey, 300, function () use ($request) {
      $query = Product::where('status', 'approved')
        ->with(['shop', 'category', 'images']);

      // Search by name
      if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
      }

      // Filter by category
      if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
      }

      // Filter by shop
      if ($request->filled('shop_id')) {
        $query->where('shop_id', $request->shop_id);
      }

      // Price range filter
      if ($request->filled('min_price')) {
        $query->where('price', '>=', $request->min_price);
      }
      if ($request->filled('max_price')) {
        $query->where('price', '<=', $request->max_price);
      }

      // Sorting
      match ($request->input('sort', 'newest')) {
        'price_asc' => $query->orderBy('price', 'asc'),
        'price_desc' => $query->orderBy('price', 'desc'),
        'newest' => $query->orderBy('created_at', 'desc'),
        default => $query->orderBy('created_at', 'desc'),
      };

      $perPage = $request->input('per_page', 12);
      return $query->paginate($perPage);
    });

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
   * Get public product details by slug
   *
   * @unauthenticated
   * @param string $slug Product slug
   * @response 200 body="{"success":true,"data":{}}"
   * @response 404 body="{"success":false,"message":"Produk tidak ditemukan."}"
   */
  public function show(string $slug): JsonResponse
  {
    $product = Cache::remember("public_product_{$slug}", 300, function () use ($slug) {
      return Product::where('slug', $slug)
        ->where('status', 'approved')
        ->with(['shop', 'category', 'images'])
        ->first();
    });

    if (!$product) {
      return response()->json([
        'success' => false,
        'message' => 'Produk tidak ditemukan.',
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => new ProductResource($product),
    ]);
  }
}
