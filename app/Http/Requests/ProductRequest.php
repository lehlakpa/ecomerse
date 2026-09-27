<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'description' => ['required', 'string', 'max:10000'],
            'sizes' => ['nullable', 'array', 'max:20'],
            'sizes.*' => ['required', 'string', 'max:40', 'distinct'],
            'colors' => ['nullable', 'array', 'max:20'],
            'colors.*' => ['required', 'string', 'max:40', 'distinct'],
            'images' => [$this->isMethod('post') ? 'required' : 'nullable', 'array', 'min:1', 'max:6'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
