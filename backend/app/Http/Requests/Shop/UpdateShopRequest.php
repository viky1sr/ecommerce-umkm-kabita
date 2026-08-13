<?php

declare(strict_types=1);

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use Illuminate\Validation\Rule;

class UpdateShopRequest extends FormRequest
{
  public function authorize(): bool
  {
        return Auth::check() && Auth::user()->role === UserRole::SELLER;
  }

  public function rules(): array
  {
    return [
      'name' => ['sometimes', 'string', 'max:255'],
      'slug' => ['sometimes', 'string', 'max:255', Rule::unique('shops', 'slug')->ignore($this->route('shop'))],
      'description' => ['nullable', 'string', 'max:1000'],
      'phone' => ['nullable', 'string', 'max:30'],
      'address' => ['nullable', 'string', 'max:500'],
      'logo' => [
        'nullable',
        'file',
        'mimes:jpeg,png,jpg,webp',
        'max:2048',
        'image',
      ],
      'banner' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:4096', 'image'],
    ];
  }

  public function bodyParameters(): array
  {
    return [
      'name' => ['description' => 'Nama toko (opsional).'],
      'description' => ['description' => 'Deskripsi toko (opsional).'],
      'logo' => ['description' => 'Logo toko (opsional, max 2MB).'],
    ];
  }
}
