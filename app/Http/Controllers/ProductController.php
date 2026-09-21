<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display the public list of products.
     */
    public function index(): Response
    {
        $products = Cache::rememberForever('products_page_products', function () {
            return Product::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });

        return Inertia::render('Products', [
            'products' => $products,
        ]);
    }
}
