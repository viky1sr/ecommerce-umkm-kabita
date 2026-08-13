<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

class SuspendUserRequest extends FormRequest
{
  public function authorize(): bool
  {
    return Auth::check() && Auth::user()->role === UserRole::ADMIN;
  }

  public function rules(): array
  {
    return [
      'reason' => ['nullable', 'string', 'max:1000'],
    ];
  }

  public function messages(): array
  {
    return [
      'reason.string' => 'Alasan penangguhan harus berupa teks.',
      'reason.max' => 'Alasan penangguhan maksimal 1000 karakter.',
    ];
  }
}
