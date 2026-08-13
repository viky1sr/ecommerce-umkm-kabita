<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\ShopCartGroupResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Services\CartCalculationService;

/**
 * @group Cart
 * @tag Cart - Shopping cart operations
 */
class CartController extends Controller
{
  /** @var CartCalculationService */
  private CartCalculationService $calculationService;

  public function __construct(CartCalculationService $calculationService)
  {
    $this->calculationService = $calculationService;
  }

  /**
   * Helper: Get or create cart for authenticated buyer.
   */
  private function getOrCreateCart(): Cart
  {
    $cart = Auth::user()->cart;

    if (!$cart) {
      $cart = Cart::create([
        'buyer_id' => Auth::id(),
      ]);
    }

    return $cart;
  }

  /**
   * Load cart with eager-loaded relationships to avoid N+1 queries.
   */
  private function loadCartWithRelations(Cart $cart): Cart
  {
    return $cart->load(['items.product:id,name,slug,price,stock,status,shop_id', 'items.product.shop:id,name,slug']);
  }

  /**
   * Retrieve the user's shopping cart
   *
   * @authenticated
   * @response 200 body="{"success":true,"data":{}}"
   */
  public function index(Request $request): JsonResponse
  {
    $cart = $this->getOrCreateCart();
    $cart = $this->loadCartWithRelations($cart);

    return response()->json([
      'success' => true,
      'data' => new CartResource($cart),
    ]);
  }

  /**
   * Add items to cart
   *
   * @authenticated
   * @requestBody required
   * @bodyParam product_id integer required "Product ID" example=1
   * @bodyParam quantity integer required "Quantity to add" example=2
   * @response 201 body="{"success":true,"message":"Barang berhasil ditambahkan ke keranjang.","data":{}}"
   * @response 422 body="{"success":false,"message":"Jumlah total melebihi stok yang tersedia."}"
   */
  public function store(AddToCartRequest $request): JsonResponse
  {
    $validated = $request->validated();
    $cart = $this->getOrCreateCart();
    $productId = $validated['product_id'];
    $quantity = $validated['quantity'];

    $cartItem = CartItem::where('cart_id', $cart->id)
      ->where('product_id', $productId)
      ->first();

    if ($cartItem) {
      // Update quantity (upsert behavior)
      $newQuantity = $cartItem->quantity + $quantity;

      // Check if new quantity exceeds stock
      $product = Product::find($productId);
      if ($newQuantity > $product->stock) {
        return response()->json([
          'success' => false,
          'message' => 'Jumlah total melebihi stok yang tersedia.',
        ], 422);
      }

      $cartItem->update(['quantity' => $newQuantity]);

      return response()->json([
        'success' => true,
        'message' => 'Jumlah barang dalam keranjang berhasil diperbarui.',
        'data' => new CartResource($this->loadCartWithRelations($cart)),
      ]);
    }

    // Create new cart item
    DB::beginTransaction();

    try {
      $cartItem = CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $productId,
        'quantity' => $quantity,
      ]);

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Barang berhasil ditambahkan ke keranjang.',
        'data' => new CartResource($this->loadCartWithRelations($cart)),
      ], 201);
    } catch (\Exception $e) {
      DB::rollBack();

      return response()->json([
        'success' => false,
        'message' => 'Gagal menambahkan barang ke keranjang.',
      ], 500);
    }
  }

  /**
   * Update cart item quantity
   *
   * @authenticated
   * @requestBody required
   * @bodyParam quantity integer required "New quantity" example=3
   * @response 200 body="{"success":true,"message":"Jumlah barang berhasil diperbarui.","data":{}}"
   * @response 403 body="{"success":false,"message":"Akses ditolak. Item keranjang ini bukan milik Anda."}"
   */
  public function update(UpdateCartItemRequest $request, int $cartItem): JsonResponse
  {
    $validated = $request->validated();
    $cartItemInstance = CartItem::findOrFail($cartItem);

    // Verify ownership
    if ($cartItemInstance->cart->buyer_id !== Auth::id()) {
      return response()->json([
        'success' => false,
        'message' => 'Akses ditolak. Item keranjang ini bukan milik Anda.',
      ], 403);
    }

    $cartItemInstance->update(['quantity' => $validated['quantity']]);

    $cart = $cartItemInstance->cart;
    $cart = $this->loadCartWithRelations($cart);

    return response()->json([
      'success' => true,
      'message' => 'Jumlah barang berhasil diperbarui.',
      'data' => new CartResource($cart),
    ]);
  }

  /**
   * Delete an item from the cart
   *
   * @authenticated
   * @response 200 body="{"success":true,"message":"Barang berhasil dihapus dari keranjang.","data":{}}"
   * @response 403 body="{"success":false,"message":"Akses ditolak. Item keranjang ini bukan milik Anda."}"
   */
  public function destroy(int $cartItem): JsonResponse
  {
    $cartItemInstance = CartItem::findOrFail($cartItem);

    // Verify ownership
    if ($cartItemInstance->cart->buyer_id !== Auth::id()) {
      return response()->json([
        'success' => false,
        'message' => 'Akses ditolak. Item keranjang ini bukan milik Anda.',
      ], 403);
    }

    $cartItemInstance->delete();

    $cart = $cartItemInstance->cart;
    $cart = $this->loadCartWithRelations($cart);

    return response()->json([
      'success' => true,
      'message' => 'Barang berhasil dihapus dari keranjang.',
      'data' => new CartResource($cart),
    ]);
  }

  /**
   * Clear all items from the cart
   *
   * @authenticated
   * @response 200 body="{"success":true,"message":"Keranjang belanja berhasil dikosongkan.","data":{}}"
   * @response 403 body="{"success":false,"message":"Akses ditolak."}"
   */
  public function clear(Request $request): JsonResponse
  {
    $cart = $this->getOrCreateCart();

    // Verify ownership
    if ($cart->buyer_id !== Auth::id()) {
      return response()->json([
        'success' => false,
        'message' => 'Akses ditolak.',
      ], 403);
    }

    $cart->items()->delete();

    return response()->json([
      'success' => true,
      'message' => 'Keranjang belanja berhasil dikosongkan.',
      'data' => new CartResource($this->loadCartWithRelations($cart)),
    ]);
  }

  /**
   * GET /api/cart/validate - Validate cart for checkout
   * Returns stock availability, grouped items by shop, and totals.
   */
  public function validateForCheckout(Request $request): JsonResponse
  {
    $cart = $this->getOrCreateCart();
    $cart->load(['items.product', 'items.product.shop']);

    $stockStatus = $this->calculationService->checkStockAvailability($cart);
    $groupsByShop = collect($this->calculationService->groupItemsByShop($cart))
      ->map(fn(array $group) => new ShopCartGroupResource((object) $group))
      ->all();
    $subtotal = $this->calculationService->calculateSubtotal($cart);
    $total = $this->calculationService->calculateTotal($cart, 'standard');
    $totalItems = $cart->items->sum('quantity');

    return response()->json([
      'success' => true,
      'data' => [
        'available' => $stockStatus['available'],
        'unavailable_items' => $stockStatus['unavailable_items'],
        'groups_by_shop' => $groupsByShop,
        'subtotal' => $subtotal,
        'total' => $total,
        'total_items' => $totalItems,
      ],
    ]);
  }
}
