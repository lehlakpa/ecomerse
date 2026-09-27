<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $cents = (int) round((float) $product->price * 100);
        $product->orders()->create([
            ...$data,
            'user_id' => $request->user()?->id,
            'size' => $data['size'] ?? null,
            'color' => $data['color'] ?? null,
            'unit_price' => $product->price,
            'total_price' => number_format($cents * $data['quantity'] / 100, 2, '.', ''),
            'status' => 'pending',
        ]);

        return redirect()->route('products.show', $product)
            ->with('success', 'Your order has been placed. We will contact you to confirm delivery.');
    }
}
