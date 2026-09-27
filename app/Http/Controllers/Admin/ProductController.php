<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('images')->latest()->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(ProductRequest $request, CloudinaryService $cloudinary): RedirectResponse
    {
        return $this->save($request, new Product, $cloudinary);
    }

    public function edit(Product $product): View
    {
        $product->load('images');

        return view('admin.products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product, CloudinaryService $cloudinary): RedirectResponse
    {
        return $this->save($request, $product, $cloudinary);
    }

    private function save(ProductRequest $request, Product $product, CloudinaryService $cloudinary): RedirectResponse
    {
        $uploads = [];
        $oldImages = $request->hasFile('images') ? $product->images()->get() : collect();
        try {
            foreach ($request->file('images', []) as $image) {
                $uploads[] = $cloudinary->upload($image);
            }

            DB::transaction(function () use ($request, $product, $uploads): void {
                $data = $request->safe()->except('images');
                $product->fill([...$data, 'rating' => $data['rating'] ?? 0, 'sizes' => $data['sizes'] ?? [], 'colors' => $data['colors'] ?? []])->save();
                if ($uploads !== []) {
                    $product->images()->delete();
                    foreach ($uploads as $upload) {
                        $product->images()->create(['image_url' => $upload['url'], 'image_public_id' => $upload['public_id']]);
                    }
                }
            });
        } catch (Throwable $exception) {
            foreach ($uploads as $upload) {
                $this->cleanImage($cloudinary, $upload['public_id']);
            }
            report($exception);

            return back()->withInput()->withErrors(['images' => 'Unable to save the product. Check image storage configuration and try again.']);
        }

        foreach ($oldImages as $image) {
            $this->cleanImage($cloudinary, $image->image_public_id);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product saved successfully.');
    }

    public function destroy(Product $product, CloudinaryService $cloudinary): RedirectResponse
    {
        if ($product->orders()->exists()) {
            return back()->withErrors(['product' => 'Products with orders cannot be deleted, so order history is preserved.']);
        }
        $images = $product->images()->get();
        $product->delete();
        foreach ($images as $image) {
            $this->cleanImage($cloudinary, $image->image_public_id);
        }

        return back()->with('success', 'Product deleted.');
    }

    private function cleanImage(CloudinaryService $cloudinary, string $publicId): void
    {
        try {
            $cloudinary->delete($publicId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
