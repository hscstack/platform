<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(): Response
    {
        $products = Product::orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('admin/Product', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        return Inertia::render('admin/ProductCreateOrEdit');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products');
            $data['image_path'] = $path;
        } elseif (! empty($data['image_url'])) {
            $data['image_path'] = $data['image_url'];
        }

        unset($data['image'], $data['image_url']);

        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('admin/ProductCreateOrEdit', [
            'product' => $product,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image_path && ! str($product->image_path)->startsWith(['http://', 'https://'])) {
                Storage::delete($product->image_path);
            }

            $path = $request->file('image')->store('products');
            $data['image_path'] = $path;
        } elseif (isset($data['image_url'])) {
            if (! empty($data['image_url'])) {
                $data['image_path'] = $data['image_url'];
            }
        }

        unset($data['image'], $data['image_url']);

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path && ! str($product->image_path)->startsWith(['http://', 'https://'])) {
            Storage::delete($product->image_path);
        }

        $product->delete();

        return redirect()
            ->back()
            ->with('success', 'Product deleted successfully.');
    }
}
