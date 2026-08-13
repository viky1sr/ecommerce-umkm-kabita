<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use Illuminate\Support\Str;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->role === UserRole::SELLER;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'gt:cost_price', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Nama produk (wajib).'],
            'description' => ['description' => 'Deskripsi produk (opsional).'],
            'price' => ['description' => 'Harga jual produk (wajib, harus > cost_price).'],
            'cost_price' => ['description' => 'Harga modal produk (opsional).'],
            'stock' => ['description' => 'Stok produk (wajib, minimal 0).'],
            'weight' => ['description' => 'Berat produk dalam gram (opsional).'],
            'category_id' => ['description' => 'ID kategori produk (wajib).'],
            'images' => ['description' => 'Gambar produk (opsional, max 5MB per file).'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $slug = Str::slug($this->name) . '-' . time();
            if (\App\Models\Product::where('slug', $slug)->exists()) {
                $v->errors()->add('name', 'Nama produk sudah digunakan.');
            }
        });
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['shop_id'] = Auth::user()->shop_id ?? Auth::user()->shop?->id;
        $data['slug'] = Str::slug($this->name) . '-' . time();
        $data['status'] = 'pending';
        return $data;
    }
}
