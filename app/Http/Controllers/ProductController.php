<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display the public list of products.
     */
    public function index(): Response
    {
        $canManage = auth()->check() && auth()->user()->can('manage products');

        if ($canManage) {
            $products = Product::orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->toArray();
        } else {
            $products = Cache::rememberForever('products_page_products', function () {
                return Product::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->toArray();
            });
        }

        return Inertia::render('Products', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        return Inertia::render('Products/CreateOrEdit');
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
        }

        unset($data['image']);

        Product::create($data);

        Cache::forget('products_page_products');

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('Products/CreateOrEdit', [
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
        }

        unset($data['image']);

        $product->update($data);

        Cache::forget('products_page_products');

        return redirect()
            ->route('products.index')
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

        Cache::forget('products_page_products');

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
