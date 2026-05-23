<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:50'],
            'customer.address' => ['required', 'string', 'max:500'],
            'customer.city' => ['required', 'string', 'max:255'],
            'customer.notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:cod'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.size' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount_code' => ['nullable', 'string'],
        ];
    }
}
