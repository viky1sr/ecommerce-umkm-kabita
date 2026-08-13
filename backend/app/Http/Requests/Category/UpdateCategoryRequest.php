<?php

declare(strict_types=1);

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

class UpdateCategoryRequest extends FormRequest
{
  public function authorize(): bool
  {
    return Auth::check() && Auth::user()->role === UserRole::ADMIN;
  }

  public function rules(): array
  {
    $categoryId = $this->route('category');
    $categoryId = is_object($categoryId) ? $categoryId->id : $categoryId;

    return [
      'name' => ['sometimes', 'string', 'max:255'],
      'slug' => ['sometimes', 'string', 'max:255', 'unique:categories,slug,' . $categoryId],
      'icon' => ['sometimes', 'nullable', 'string', 'max:32'],
      'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
    ];
  }

  public function messages(): array
  {
    return [
      'name.string' => 'Nama kategori harus berupa teks.',
      'name.max' => 'Nama kategori maksimal 255 karakter.',
      'slug.string' => 'Slug harus berupa teks.',
      'slug.max' => 'Slug maksimal 255 karakter.',
      'slug.unique' => 'Slug sudah digunakan.',
    ];
  }

  public function bodyParameters(): array
  {
    return [
      'name' => ['description' => 'Nama kategori (opsional).'],
      'slug' => ['description' => 'Slug kategori (opsional).'],
    ];
  }
}
