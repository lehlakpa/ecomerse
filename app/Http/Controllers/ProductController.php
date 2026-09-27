<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        $products = Product::with('images')
            ->when($search, function ($query, $search) {

                $query->where(function ($q) use ($search) {

                    $q->where(
                        'title',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        );
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view(
            'products.index',
            compact('products', 'search')
        );
    }

    public function show(Product $product): View
    {
        $product->load('images');

        return view(
            'products.show',
            compact('product')
        );
    }
}
