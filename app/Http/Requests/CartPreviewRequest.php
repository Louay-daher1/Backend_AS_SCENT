<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.size' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount_code' => ['nullable', 'string'],
        ];
    }
}
