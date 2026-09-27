<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string', 'max:255'],
            'size' => [count($product->sizes ?? []) ? 'required' : 'nullable', 'string', Rule::in($product->sizes ?? [])],
            'color' => [count($product->colors ?? []) ? 'required' : 'nullable', 'string', Rule::in($product->colors ?? [])],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}
