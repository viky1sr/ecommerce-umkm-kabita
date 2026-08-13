<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * @group Category
 * @tag Category - Product categories management
 */
class CategoryController extends Controller
{
  /**
   * @unauthenticated
   * List all product categories
   *
   * @response 200 body="{"success":true,"data":[{}]}"
   */
  public function index(): JsonResponse
  {
    $categories = Cache::remember('categories_all', 300, function () {
      return Category::all();
    });

    return response()->json([
      'success' => true,
      'data' => CategoryResource::collection($categories),
    ]);
  }

  /**
   * @unauthenticated
   * Get category details with products
   *
   * @param string $slug Category slug
   * @response 200 body="{"success":true,"data":{}}"
   * @response 404 body="{"success":false,"message":"Kategori tidak ditemukan."}"
   */
  public function show(string $slug): JsonResponse
  {
    $category = Cache::remember("category_public_v2_{$slug}", 300, function () use ($slug) {
      return Category::where('slug', $slug)->with(['products' => function ($query) {
        $query->where('status', 'approved')->with(['shop', 'category', 'images']);
      }])->first();
    });

    if (!$category) {
      return response()->json([
        'success' => false,
        'message' => 'Kategori tidak ditemukan.',
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => new CategoryResource($category),
    ]);
  }

  /**
   * Create a new product category (Admin only)
   *
   * @authenticated
   * @requestBody required
   * @bodyParam name string required "Category name" example=Elektronik
   * @bodyParam description string "Category description" example=Perangkat elektronik
   * @response 201 body="{"success":true,"message":"Kategori berhasil dibuat.","data":{}}"
   */
  public function store(CreateCategoryRequest $request): JsonResponse
  {
    $data = $request->validated();

    if (empty($data['slug'])) {
      $data['slug'] = Str::slug($data['name']);
    }

    $category = Category::create($data);
    Cache::forget('categories_all');

    return response()->json([
      'success' => true,
      'message' => 'Kategori berhasil dibuat.',
      'data' => new CategoryResource($category),
    ], 201);
  }

  /**
   * PUT /api/categories/{category} - Update category (Admin only)
   *
   * @authenticated
   * @requestBody required
   * @bodyParam name string "Category name" example=Elektronik
   * @bodyParam description string "Category description" example=Perangkat elektronik
   * @response 200 body="{"success":true,"message":"Kategori berhasil diperbarui.","data":{}}"
   */
  public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
  {
    $oldSlug = $category->slug;
    $data = $request->validated();

    if (isset($data['slug']) && empty($data['slug'])) {
      $data['slug'] = Str::slug($data['name'] ?? $category->name);
    }

    $category->update($data);
    Cache::forget('categories_all');
    Cache::forget("category_{$oldSlug}");
    Cache::forget("category_{$category->slug}");

    return response()->json([
      'success' => true,
      'message' => 'Kategori berhasil diperbarui.',
      'data' => new CategoryResource($category),
    ]);
  }

  /**
   * DELETE /api/categories/{category} - Delete category (Admin only)
   *
   * @authenticated
   * @response 200 body="{"success":true,"message":"Kategori berhasil dihapus."}"
   * @response 409 body="{"success":false,"message":"Kategori tidak dapat dihapus karena masih memiliki produk."}"
   */
  public function destroy(Category $category): JsonResponse
  {
    if ($category->products()->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Kategori tidak dapat dihapus karena masih memiliki produk.',
      ], 409);
    }

    $slug = $category->slug;
    $category->delete();
    Cache::forget('categories_all');
    Cache::forget("category_{$slug}");

    return response()->json([
      'success' => true,
      'message' => 'Kategori berhasil dihapus.',
    ]);
  }
}
