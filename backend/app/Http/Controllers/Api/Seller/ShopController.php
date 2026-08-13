<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CreateShopRequest;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @group Shop
 * @tag Shop - Shop management for sellers
 */
class ShopController extends Controller
{
  /**
   * Create a new shop
   *
   * @authenticated
   * @requestBody required
   * @bodyParam name string required "Shop name" example=Toko Contoh
   * @bodyParam description string "Shop description" example=Toko online terpercaya
   * @bodyParam address string "Shop address" example=Jl. Contoh No. 123
   * @bodyParam phone string "Shop phone number" example=081234567890
   * @bodyParam logo file "Shop logo image" example=logo.jpg
   * @response 201 body="{"success":true,"message":"Toko berhasil dibuat. Menunggu verifikasi admin.","data":{}}"
   */
  public function create(CreateShopRequest $request): JsonResponse
  {
    $data = $request->validated();
    // $data['seller_id'] = auth()->user()->id;
    $data['seller_id'] = Auth::id();
    $data['slug'] = Str::slug($data['name']) . '-' . time();
    $data['status'] = 'pending';

    if ($request->hasFile('logo')) {
      $data['logo'] = $request->file('logo')->store('logos', 'public');
    }
    if ($request->hasFile('banner')) {
      $data['banner'] = $request->file('banner')->store('banners', 'public');
    }

    $shop = Shop::create($data);

    return response()->json([
      'success' => true,
      'message' => 'Toko berhasil dibuat. Menunggu verifikasi admin.',
      'data' => new ShopResource($shop),
    ], 201);
  }

  /**
   * Get logged-in seller's own shop
   * @authenticated
   * @response 200 body="{"success":true,"data":{}}"
   * @response 404 body="{"success":false,"message":"Anda belum memiliki toko."}"
   */
  public function myShop(): JsonResponse
  {
    $shop = Auth::user()->shop;

    if (!$shop) {
      return response()->json([
        'success' => false,
        'message' => 'Anda belum memiliki toko.',
      ], 404);
    }

    $shop->load('seller');

    return response()->json([
      'success' => true,
      'data' => new ShopResource($shop),
    ]);
  }

  /**
   * Update shop profile
   *
   * @authenticated
   * @requestBody required
   * @bodyParam name string "Shop name" example=Toko Contoh
   * @bodyParam description string "Shop description" example=Toko online terpercaya
   * @bodyParam address string "Shop address" example=Jl. Contoh No. 123
   * @bodyParam phone string "Shop phone number" example=081234567890
   * @bodyParam logo file "Shop logo image" example=logo.jpg
   * @response 200 body="{"success":true,"message":"Profil toko berhasil diperbarui.","data":{}}"
   * @response 403 body="{"success":false,"message":"Akses ditolak. Toko ini bukan milik Anda."}"
   */
  public function update(UpdateShopRequest $request, Shop $shop): JsonResponse
  {
    if ($shop->seller_id !== Auth::id()) {
      return response()->json([
        'success' => false,
        'message' => 'Akses ditolak. Toko ini bukan milik Anda.',
      ], 403);
    }

    $data = $request->validated();
    if (isset($data['slug'])) {
      $data['slug'] = Str::slug($data['slug']);
    }

    if ($request->hasFile('logo')) {
      if ($shop->logo) {
        Storage::disk('public')->delete($shop->logo);
      }
      $data['logo'] = $request->file('logo')->store('logos', 'public');
    }
    if ($request->hasFile('banner')) {
      if ($shop->banner) {
        Storage::disk('public')->delete($shop->banner);
      }
      $data['banner'] = $request->file('banner')->store('banners', 'public');
    }

    $shop->update($data);

    return response()->json([
      'success' => true,
      'message' => 'Profil toko berhasil diperbarui.',
      'data' => new ShopResource($shop),
    ]);
  }

  /**
   * Get public shop details by slug
   *
   * @unauthenticated
   * @param string $slug Shop slug
   * @response 200 body="{"success":true,"data":{}}"
   * @response 404 body="{"success":false,"message":"Toko tidak ditemukan."}"
   */
  public function showPublic(string $slug): JsonResponse
  {
    $shop = Shop::where('slug', $slug)
      ->with(['products' => function ($q) {
        $q->where('status', 'approved');
      }])
      ->first();

    if (!$shop) {
      return response()->json([
        'success' => false,
        'message' => 'Toko tidak ditemukan.',
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => new ShopResource($shop),
    ]);
  }
}
