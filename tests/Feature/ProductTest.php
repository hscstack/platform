<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

test('the products page renders dynamic products from database', function () {
    Product::create([
        'name' => 'Demo Platform',
        'description' => 'Demo description',
        'link' => 'https://example.com',
        'open_type' => '_blank',
        'users' => '500+ Users',
        'is_active' => true,
        'order' => 1,
    ]);

    Product::create([
        'name' => 'Inactive Platform',
        'description' => 'Inactive description',
        'link' => 'https://example.com/inactive',
        'open_type' => '_self',
        'is_active' => false,
        'order' => 2,
    ]);

    $response = $this->get('/products');

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Products')
        ->has('products', 1)
        ->where('products.0.name', 'Demo Platform')
    );
});

test('products page products are cached forever and cleared on create, update, and delete', function () {
    Cache::forget('products_page_products');

    expect(Cache::has('products_page_products'))->toBeFalse();

    $this->get('/products')->assertStatus(200);

    expect(Cache::has('products_page_products'))->toBeTrue();

    // Invalidate on create
    $product = Product::create([
        'name' => 'New Product',
        'description' => 'New description',
        'link' => 'https://newproduct.com',
        'open_type' => '_blank',
        'is_active' => true,
    ]);

    expect(Cache::has('products_page_products'))->toBeFalse();

    // Re-cache
    $this->get('/products')->assertStatus(200);
    expect(Cache::has('products_page_products'))->toBeTrue();

    // Invalidate on update
    $product->update(['name' => 'Renamed Product']);
    expect(Cache::has('products_page_products'))->toBeFalse();

    // Re-cache
    $this->get('/products')->assertStatus(200);
    expect(Cache::has('products_page_products'))->toBeTrue();

    // Invalidate on delete
    $product->delete();
    expect(Cache::has('products_page_products'))->toBeFalse();
});

test('unauthorized users cannot access admin products management', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/products');
    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');

    $userWithAdmin = adminUserWithPermissions(['view admin']);
    $response = $this->actingAs($userWithAdmin)->get('/admin/products');
    $response->assertStatus(302);
    $response->assertSessionHas('error', 'You do not have permission to perform this action.');
});

test('users with manage products permission can manage products in admin panel', function () {
    Storage::fake();

    $adminUser = adminUserWithPermissions(['view admin', 'manage products']);

    // List products
    $response = $this->actingAs($adminUser)->get('/admin/products');
    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('admin/Product')
        ->has('products')
    );

    // Show create form
    $this->actingAs($adminUser)->get('/admin/products/create')->assertStatus(200);

    // Store product
    $file = UploadedFile::fake()->image('banner.png', 800, 450);

    $storeResponse = $this->actingAs($adminUser)->post('/admin/products', [
        'name' => 'Test Product',
        'description' => 'A great new platform.',
        'image' => $file,
        'users' => '1000+ Users',
        'link' => 'https://testplatform.com',
        'open_type' => '_blank',
        'button_text' => 'Launch Test',
        'order' => 1,
        'is_active' => true,
    ]);

    $storeResponse->assertRedirect(route('admin.products.index'));

    $product = Product::where('name', 'Test Product')->first();
    expect($product)->not->toBeNull()
        ->and($product->users)->toBe('1000+ Users')
        ->and($product->open_type)->toBe('_blank')
        ->and($product->button_text)->toBe('Launch Test');

    Storage::assertExists($product->image_path);

    // Show edit form
    $this->actingAs($adminUser)->get("/admin/products/edit/{$product->id}")->assertStatus(200);

    // Update product
    $updateResponse = $this->actingAs($adminUser)->post("/admin/products/edit/{$product->id}/patch", [
        'name' => 'Updated Test Product',
        'description' => 'Updated description.',
        'users' => '2000+ Users',
        'link' => 'https://updated.com',
        'open_type' => '_self',
        'button_text' => 'Go to Updated',
        'order' => 5,
        'is_active' => true,
    ]);

    $updateResponse->assertRedirect(route('admin.products.index'));
    $product->refresh();
    expect($product->name)->toBe('Updated Test Product')
        ->and($product->users)->toBe('2000+ Users')
        ->and($product->open_type)->toBe('_self');

    // Delete product
    $deleteResponse = $this->actingAs($adminUser)->delete("/admin/products/{$product->id}");
    $deleteResponse->assertRedirect();
    expect(Product::find($product->id))->toBeNull();
});
