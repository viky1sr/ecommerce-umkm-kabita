<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['buyer', 'shop.seller', 'items.product.images', 'payment']);
        if ($request->filled('status') && $request->input('status') !== 'all') $query->where('status', $request->input('status'));
        if ($request->filled('search')) $query->where(function ($q) use ($request) {
            $term = '%' . $request->input('search') . '%';
            $q->where('order_number', 'like', $term)->orWhereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', $term)->orWhere('email', 'like', $term));
        });
        $query->latest();
        $orders = $query->paginate(min((int) $request->input('per_page', 15), 100));
        return response()->json(['success' => true, 'data' => OrderResource::collection($orders), 'meta' => [
            'current_page' => $orders->currentPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total(), 'last_page' => $orders->lastPage(),
        ]]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['buyer', 'shop.seller', 'items.product.images', 'payment']);
        return response()->json(['success' => true, 'data' => new OrderResource($order)]);
    }
}
