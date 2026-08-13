<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Analytics
 * @tag Analytics - Business analytics & reports (Admin endpoints)
 */
class AnalyticsController extends Controller
{
  /**
   * Get platform-wide statistics and analytics
   *
   * @authenticated
   * @response 200 body="{"success":true,"data":{"total_users":100,"users_by_role":{"buyer":60,"seller":40},"total_shops":50,"shops_by_status":{"verified":40,"pending":10},"verified_shops":40,"pending_shops":10,"total_products":200,"monthly_transactions":[{"month":1,"transactions":50,"revenue":"500000.00"}],"platform_revenue":"1000000.00"}}"
   */
  public function platform(): JsonResponse
  {
    // Total users by role
    $usersByRole = User::selectRaw('role, COUNT(*) as count')
      ->groupBy('role')
      ->pluck('count', 'role')
      ->toArray();

    // Total shops by status
    $shopsByStatus = Shop::selectRaw('status, COUNT(*) as count')
      ->groupBy('status')
      ->pluck('count', 'status')
      ->toArray();

    $totalUsers = User::count();
    $totalShops = Shop::count();
    $totalProducts = Product::where('status', 'approved')->count();

    // Monthly transactions
    $monthExpression = DB::connection()->getDriverName() === 'sqlite'
      ? "CAST(strftime('%m', created_at) AS INTEGER)"
      : 'MONTH(created_at)';
    $yearExpression = DB::connection()->getDriverName() === 'sqlite'
      ? "CAST(strftime('%Y', created_at) AS INTEGER)"
      : 'YEAR(created_at)';
    $monthlyTransactions = Order::selectRaw("{$monthExpression} as month, {$yearExpression} as year, COUNT(*) as count, SUM(total_amount) as revenue")
      ->whereYear('created_at', now()->year)
      ->groupBy('year', 'month')
      ->orderBy('year', 'asc')
      ->orderBy('month', 'asc')
      ->get();

    $monthlyData = $monthlyTransactions->map(fn($row) => [
            'month' => $row->month,
            'year' => (int) $row->year,
            'transactions' => (int) $row->count,
      'revenue' => number_format((float) $row->revenue, 2, '.', ''),
    ]);

    // Platform total revenue
    $platformRevenue = Order::where('status', 'delivered')->sum('total_amount');

    return response()->json([
      'success' => true,
      'data' => [
        'total_users' => $totalUsers,
        'users_by_role' => $usersByRole,
        'total_shops' => $totalShops,
        'shops_by_status' => $shopsByStatus,
        'verified_shops' => $shopsByStatus['verified'] ?? 0,
        'pending_shops' => $shopsByStatus['pending'] ?? 0,
        'total_products' => $totalProducts,
        'monthly_transactions' => $monthlyData,
        'platform_revenue' => number_format((float) $platformRevenue, 2, '.', ''),
      ],
    ]);
  }

  /**
   * Get sales analytics by period
   * @param string $period daily|weekly|monthly
   *
   * @queryParam period string "Period: daily, weekly, or monthly" example=monthly
   * @response 200 body="{"success":true,"data":[{"date":"2026-01","revenue":"500000.00","orders_count":25}]}"
   */
  public function sales(Request $request): JsonResponse
  {
    $request->validate([
      'period' => ['sometimes', 'in:daily,weekly,monthly'],
    ]);

    $period = $request->input('period', 'monthly');

    $isSqlite = DB::connection()->getDriverName() === 'sqlite';
    $groupBy = match ($period) {
      'daily' => 'DATE(created_at)',
      'weekly' => $isSqlite ? "strftime('%Y-%W', created_at)" : 'YEARWEEK(created_at, 1)',
      default => $isSqlite ? "strftime('%Y-%m', created_at)" : 'DATE_FORMAT(created_at, "%Y-%m")',
    };

    $rows = DB::table('orders')
      ->where('status', 'delivered')
      ->selectRaw("{$groupBy} as label, COUNT(*) as orders_count, SUM(total_amount) as revenue")
      ->groupBy('label')
      ->orderBy('label', 'desc')
      ->limit(30)
      ->get()
      ->reverse();

    $data = $rows->map(fn($r) => [
      'date' => $r->label,
      'revenue' => number_format((float) ($r->revenue ?? 0), 2, '.', ''),
      'orders_count' => (int) ($r->orders_count ?? 0),
    ]);

    return response()->json([
      'success' => true,
      'data' => $data,
    ]);
  }

  /**
   * @queryParam per_page integer "Items per page" example=10
   * @response 200 body="{"success":true,"data":[{"id":1,"name":"Toko A","total_orders":50,"total_revenue":"500000.00"}]}"
   *
   * Get top sellers by revenue
   */
  public function topSellers(Request $request): JsonResponse
  {
    $perPage = $request->input('per_page', 10);

    $sellers = DB::table('shops')
      ->leftJoin('orders', 'shops.id', '=', 'orders.shop_id')
      ->where('orders.status', 'delivered')
      ->select(
        'shops.id',
        'shops.name',
        DB::raw('COUNT(orders.id) as total_orders'),
        DB::raw('SUM(orders.total_amount) as total_revenue')
      )
      ->groupBy('shops.id', 'shops.name')
      ->orderByDesc('total_revenue')
      ->limit($perPage)
      ->get();

    $data = $sellers->map(fn($s) => [
      'id' => $s->id,
      'name' => $s->name,
      'total_orders' => (int) $s->total_orders,
      'total_revenue' => number_format((float) $s->total_revenue, 2, '.', ''),
    ]);

    return response()->json([
      'success' => true,
      'data' => $data,
    ]);
  }

  /**
   * @queryParam per_page integer "Items per page" example=10
   * @response 200 body="{"success":true,"data":[{"id":1,"name":"Produk A","slug":"produk-a","price":"100000.00","total_qty_sold":100,"total_revenue":"10000000.00"}]}"
   *
   * Get top selling products
   */
  public function topProducts(Request $request): JsonResponse
  {
    $perPage = $request->input('per_page', 10);

    $products = DB::table('order_items')
      ->join('products', 'order_items.product_id', '=', 'products.id')
      ->join('shops', 'products.shop_id', '=', 'shops.id')
      ->join('orders', 'order_items.order_id', '=', 'orders.id')
      ->where('orders.status', 'delivered')
      ->select(
        'products.id',
        'products.name',
        'products.slug',
        'products.price',
        'shops.name as shop_name',
        DB::raw('SUM(order_items.quantity) as total_qty_sold'),
        DB::raw('SUM(order_items.quantity * order_items.price_snapshot) as total_revenue')
      )
      ->groupBy('products.id', 'products.name', 'products.slug', 'products.price', 'shops.name')
      ->orderByDesc('total_qty_sold')
      ->limit($perPage)
      ->get();

    $data = $products->map(fn($p) => [
      'id' => $p->id,
      'name' => $p->name,
      'slug' => $p->slug,
      'price' => number_format((float) $p->price, 2, '.', ''),
      'shop_name' => $p->shop_name,
      'total_qty_sold' => (int) $p->total_qty_sold,
      'total_revenue' => number_format((float) $p->total_revenue, 2, '.', ''),
    ]);

    return response()->json([
      'success' => true,
      'data' => $data,
    ]);
  }

  public function categoryRevenue(Request $request): JsonResponse
  {
    $rows = DB::table('order_items')
      ->join('products', 'order_items.product_id', '=', 'products.id')
      ->join('categories', 'products.category_id', '=', 'categories.id')
      ->join('orders', 'order_items.order_id', '=', 'orders.id')
      ->where('orders.status', 'delivered')
      ->select('categories.id', 'categories.name', DB::raw('SUM(order_items.quantity * order_items.price_snapshot) as revenue'))
      ->groupBy('categories.id', 'categories.name')
      ->orderByDesc('revenue')
      ->limit((int) $request->input('per_page', 10))
      ->get()
      ->map(fn ($row) => [
        'id' => $row->id,
        'name' => $row->name,
        'revenue' => number_format((float) $row->revenue, 2, '.', ''),
      ]);

    return response()->json(['success' => true, 'data' => $rows]);
  }
}
