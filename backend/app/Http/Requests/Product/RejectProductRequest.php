<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

class RejectProductRequest extends FormRequest
{
  public function authorize(): bool
  {
        return Auth::check() && Auth::user()->role === UserRole::ADMIN;
  }

  public function rules(): array
  {
    return [
      'rejection_reason' => ['required', 'string', 'max:1000'],
    ];
  }

  public function messages(): array
  {
    return [
      'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
      'rejection_reason.string' => 'Alasan penolakan harus berupa teks.',
      'rejection_reason.max' => 'Alasan penolakan maksimal 1000 karakter.',
    ];
  }

  public function bodyParameters(): array
  {
    return [
      'rejection_reason' => ['description' => 'Alasan penolakan produk (wajib, maksimal 1000 karakter).'],
    ];
  }
}
