<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Models\CartItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'quantity' => [
        'required',
        'integer',
        'min:1',
        function (string $attribute, mixed $value, callable $fail) {
          $cartItem = CartItem::with('product')->find($this->route('cartItem'));

          if ($cartItem && $value > $cartItem->product->stock) {
            $fail('Jumlah yang diminta melebihi stok yang tersedia.');
          }
        },
      ],
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'quantity.required' => 'Jumlah barang harus diisi.',
      'quantity.integer' => 'Jumlah barang harus berupa angka.',
      'quantity.min' => 'Jumlah barang minimal adalah 1.',
    ];
  }
}
