<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Analytics
 * @tag Analytics - Business analytics & reports (Seller endpoints)
 */
class AnalyticsController extends Controller
{
  /**
   * Get seller's shop overview statistics
   *
   * @authenticated
   * @response 200 body="{"success":true,"data":{"total_products":10,"total_orders":25,"total_revenue":"500000.00","pending_orders_count":5}}"
   * @response 404 body="{"success":false,"message":"Anda belum memiliki toko."}"
   */
  public function overview(): JsonResponse
  {
    $shop = auth()->user()->shop;

    if (!$shop) {
      return response()->json([
        'success' => false,
        'message' => 'Anda belum memiliki toko.',
      ], 404);
    }

    $shopId       = $shop->id;
    $monthStart   = now()->startOfMonth();
    $monthEnd     = now()->endOfMonth();

    $totalProducts   = Product::where('shop_id', $shopId)->count();
    $totalOrders     = Order::where('shop_id', $shopId)
      ->whereBetween('created_at', [$monthStart, $monthEnd])
      ->count();
    $totalRevenue    = Order::where('shop_id', $shopId)
      ->whereBetween('created_at', [$monthStart, $monthEnd])
      ->sum('total_amount');
    $pendingOrders   = Order::where('shop_id', $shopId)
      ->where('status', 'pending')
      ->count();

    return response()->json([
      'success' => true,
      'data'    => [
        'total_products'      => $totalProducts,
        'total_orders'        => $totalOrders,
        'total_revenue'       => number_format((float) $totalRevenue, 2, '.', ''),
        'pending_orders_count' => $pendingOrders,
      ],
    ]);
  }

  /**
   * Get seller's sales analytics
   * @param string $period daily|weekly|monthly
   * @param string $start_date Start date (Y-m-d)
   * @param string $end_date End date (Y-m-d)
   *
   * @queryParam period string "Period: daily, weekly, or monthly" example=daily
   * @queryParam start_date string "Start date (Y-m-d)" example=2026-01-01
   * @queryParam end_date string "End date (Y-m-d)" example=2026-01-31
   * @response 200 body="{"success":true,"data":[{"date":"2026-01-01","revenue":"150000.00","orders_count":5}]}"
   * @response 404 body="{"success":false,"message":"Anda belum memiliki toko."}"
   */
  public function sales(Request $request): JsonResponse
  {
    $request->validate([
      'period'    => ['sometimes', 'in:daily,weekly,monthly'],
      'start_date' => ['sometimes', 'date'],
      'end_date'  => ['sometimes', 'date', 'after_or_equal:start_date'],
    ]);

    $shop = auth()->user()->shop;

    if (!$shop) {
      return response()->json([
        'success' => false,
        'message' => 'Anda belum memiliki toko.',
      ], 404);
    }

    $shopId      = $shop->id;
    $period      = $request->input('period', 'daily');
    $startDate   = $request->input('start_date', now()->startOfMonth());
    $endDate     = $request->input('end_date', now()->endOfMonth());

    $isSqlite = DB::connection()->getDriverName() === 'sqlite';
    $groupBy = match ($period) {
      'weekly'  => $isSqlite ? "strftime('%Y-%W', created_at)" : 'YEARWEEK(created_at, 1)',
      'monthly' => $isSqlite ? "strftime('%Y-%m', created_at)" : 'DATE_FORMAT(created_at, "%Y-%m")',
      default   => 'DATE(created_at)',
    };

    $rows = DB::table('orders')
      ->where('shop_id', $shopId)
      ->whereBetween('created_at', [$startDate, $endDate])
      ->selectRaw("{$groupBy} AS label, COUNT(*) AS orders_count, SUM(total_amount) AS revenue")
      ->groupBy('label')
      ->orderBy('label')
      ->get();

    $data = $rows->map(fn($r) => [
      'date'          => $r->label,
      'revenue'       => number_format((float) ($r->revenue ?? 0), 2, '.', ''),
      'orders_count'  => (int) ($r->orders_count ?? 0),
    ]);

    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }

  /**
   * Get top selling products for the seller's shop
   */
  public function topProducts(): JsonResponse
  {
    $shop = auth()->user()->shop;

    if (!$shop) {
      return response()->json([
        'success' => false,
        'message' => 'Anda belum memiliki toko.',
      ], 404);
    }

    $products = DB::table('order_items')
      ->join('products', 'order_items.product_id', '=', 'products.id')
      ->where('products.shop_id', $shop->id)
      ->select(
        'products.id',
        'products.name',
        'products.slug',
        'products.price',
        'products.cost_price',
        DB::raw('SUM(order_items.quantity) AS total_qty_sold'),
        DB::raw('SUM(order_items.quantity * order_items.price_snapshot) AS total_revenue'),
        DB::raw('SUM(order_items.quantity * order_items.cost_snapshot) AS total_cost'),
      )
      ->groupBy('products.id', 'products.name', 'products.slug', 'products.price', 'products.cost_price')
      ->orderByDesc('total_qty_sold')
      ->limit(10)
      ->get();

    $data = $products->map(fn($p) => [
      'id'             => $p->id,
      'name'           => $p->name,
      'slug'           => $p->slug,
      'total_qty_sold' => (int) $p->total_qty_sold,
      'revenue'        => number_format((float) $p->total_revenue, 2, '.', ''),
      'profit'         => number_format((float) ($p->total_revenue - $p->total_cost), 2, '.', ''),
    ]);

    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }

  /**
   * Get low stock products for the seller's shop
   */
  public function lowStockProducts(): JsonResponse
  {
    $shop = auth()->user()->shop;

    if (!$shop) {
      return response()->json([
        'success' => false,
        'message' => 'Anda belum memiliki toko.',
      ], 404);
    }

    $products = Product::where('shop_id', $shop->id)
      ->where('stock', '<', 10)
      ->orderBy('stock', 'asc')
      ->select(['id', 'name', 'slug', 'stock', 'price', 'cost_price'])
      ->get();

    $data = $products->map(fn($p) => [
      'id'         => $p->id,
      'name'       => $p->name,
      'slug'       => $p->slug,
      'stock'      => $p->stock,
      'price'      => $p->price,
      'cost_price' => $p->cost_price,
    ]);

    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }
}
