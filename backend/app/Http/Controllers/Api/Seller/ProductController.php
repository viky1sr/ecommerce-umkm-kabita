<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @group Product
 * @tag Product - Product management for sellers
 */
class ProductController extends Controller
{
    /**
     * List products for the seller's shop
     *
     * @authenticated
     * @queryParam search string "Search by product name" example=sepatu
     * @queryParam category_id integer "Filter by category ID" example=1
     * @queryParam status string "Filter by product status" example=active
     * @queryParam sort string "Sort: newest|oldest|price_asc|price_desc" default=newest
     * @query_param per_page integer "Items per page" default=15
     * @response 200 body="{"success":true,"data":[{}],"meta":{"current_page":1,"per_page":15,"total":50,"last_page":4}}"
     * @response 404 body="{"success":false,"message":"Anda belum memiliki toko."}"
     */
    public function index(Request $request): JsonResponse
    {
        $shop = Auth::user()->shop;
        if (!$shop) return response()->json(['success' => false, 'message' => 'Anda belum memiliki toko.'], 404);
        $query = Product::with(['category', 'images'])->where('shop_id', $shop->id);
        if ($request->filled('search')) $query->where('name', 'like', '%' . $request->string('search') . '%');
        if ($request->filled('category_id')) $query->where('category_id', $request->integer('category_id'));
        if ($request->filled('status') && $request->input('status') !== 'all') $query->where('status', $request->input('status'));
        match ($request->input('sort', 'newest')) {
            'oldest' => $query->oldest(), 'price_asc' => $query->orderBy('price'), 'price_desc' => $query->orderByDesc('price'), default => $query->latest(),
        };
        $products = $query->paginate(min((int) $request->input('per_page', 15), 100));
        return response()->json(['success' => true, 'data' => ProductResource::collection($products), 'meta' => [
            'current_page' => $products->currentPage(), 'per_page' => $products->perPage(), 'total' => $products->total(), 'last_page' => $products->lastPage(),
        ]]);
    }

    /**
     * Create a new product for the seller's shop
     *
     * @authenticated
     * @requestBody required
     * @bodyParam name string required "Product name" example=Sepatu Sneakers
     * @bodyParam description string "Product description" example=Sepatu sneakers casual
     * @bodyParam price number required "Product price" example=250000
     * @bodyParam cost_price number "Product cost price" example=150000
     * @bodyParam stock integer required "Product stock" example=100
     * @bodyParam category_id integer required "Category ID" example=1
     * @bodyParam status string "Product status: pending|active|rejected" example=pending
     * @response 201 body="{"success":true,"message":"Produk berhasil dibuat. Menunggu verifikasi admin.","data":{}}"
     * @response 404 body="{"success":false,"message":"Anda belum memiliki toko."}"
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $shop = Auth::user()->shop;
        if (!$shop) return response()->json(['success' => false, 'message' => 'Anda belum memiliki toko.'], 404);
        $data = $request->validated();
        $data['shop_id'] = $shop->id;
        $product = Product::create($data);
        $this->storeImages($product, $request);
        return response()->json(['success' => true, 'message' => 'Produk berhasil dibuat. Menunggu verifikasi admin.', 'data' => new ProductResource($product->load(['category', 'images']))], 201);
    }

    /**
     * Get product details for the seller's shop
     *
     * @authenticated
     * @param string $slug Product slug
     * @response 200 body="{"success":true,"data":{}}"
     * @response 403 body="{"success":false,"message":"Akses ditolak. Produk ini bukan milik toko Anda."}"
     * @response 404 body="{"success":false,"message":"Produk tidak ditemukan."}"
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->ownedProduct($slug);
        if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        return response()->json(['success' => true, 'data' => new ProductResource($product->load(['category', 'images']))]);
    }

    /**
     * Update a product for the seller's shop
     *
     * @authenticated
     * @requestBody required
     * @bodyParam name string "Product name" example=Sepatu Sneakers
     * @bodyParam description string "Product description" example=Sepatu sneakers casual
     * @bodyParam price number "Product price" example=250000
     * @bodyParam cost_price number "Product cost price" example=150000
     * @bodyParam stock integer "Product stock" example=100
     * @bodyParam category_id integer "Category ID" example=1
     * @bodyParam status string "Product status: pending|active|rejected" example=active
     * @response 200 body="{"success":true,"message":"Produk berhasil diperbarui.","data":{}}"
     * @response 403 body="{"success":false,"message":"Akses ditolak. Produk ini bukan milik toko Anda."}"
     * @response 404 body="{"success":false,"message":"Produk tidak ditemukan."}"
     */
    public function update(UpdateProductRequest $request, string $slug): JsonResponse
    {
        $product = $this->ownedProduct($slug);
        if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        $data = $request->validated();
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']) . '-' . $product->id;
        $data['status'] = 'pending';
        $product->update($data);
        foreach ($request->input('delete_images', []) as $imageId) {
            $image = $product->images()->whereKey($imageId)->first();
            if ($image) { Storage::disk('public')->delete($image->image_path); $image->delete(); }
        }
        $this->storeImages($product, $request);
        return response()->json(['success' => true, 'message' => 'Produk berhasil diperbarui.', 'data' => new ProductResource($product->load(['category', 'images']))]);
    }

    /**
     * Delete a product from the seller's shop
     *
     * @authenticated
     * @response 200 body="{"success":true,"message":"Produk berhasil dihapus."}"
     * @response 403 body="{"success":false,"message":"Akses ditolak. Produk ini bukan milik toko Anda."}"
     * @response 404 body="{"success":false,"message":"Produk tidak ditemukan."}"
     */
    public function destroy(string $slug): JsonResponse
    {
        $product = $this->ownedProduct($slug);
        if (!$product) return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        if ($product->hasActiveOrders()) return response()->json(['success' => false, 'message' => 'Produk tidak dapat dihapus karena memiliki pesanan aktif.'], 409);
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus.']);
    }

    private function ownedProduct(string $slug): ?Product
    {
        $shop = Auth::user()->shop;
        return $shop ? Product::where('shop_id', $shop->id)->where('slug', $slug)->first() : null;
    }

    private function storeImages(Product $product, Request $request): void
    {
        foreach ($request->file('images', []) as $index => $image) {
            $product->images()->create(['image_path' => $image->store('products', 'public'), 'order_column' => $product->images()->count() + $index]);
        }
    }
}
