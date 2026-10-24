<?php

use App\Models\Blog;
use App\Models\Node;
use App\Models\Notice;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

test('the homepage loads successfully', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('featured blogs and notice are cached on homepage', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'is_featured' => true,
        'is_published' => true,
    ]);
    $notice = Notice::singleton();
    $notice->update([
        'title' => 'Important Notice',
        'is_active' => true,
    ]);

    expect(Cache::has('home_page_featured_blogs'))->toBeFalse()
        ->and(Cache::has('home_page_notice'))->toBeFalse();

    $this->get('/')->assertStatus(200);

    expect(Cache::has('home_page_featured_blogs'))->toBeTrue()
        ->and(Cache::has('home_page_notice'))->toBeTrue();

    // Cache should be invalidated when notice is updated
    $notice->update(['title' => 'Updated Notice']);
    expect(Cache::has('home_page_notice'))->toBeFalse()
        ->and(Cache::has('home_page_featured_blogs'))->toBeFalse();

    // Re-cache and verify blog change invalidates cache
    $this->get('/')->assertStatus(200);
    expect(Cache::has('home_page_featured_blogs'))->toBeTrue();

    $blog->update(['title' => 'Updated Blog Title']);
    expect(Cache::has('home_page_featured_blogs'))->toBeFalse();
});

test('the about us page loads successfully', function () {
    $response = $this->get('/about-us');

    $response->assertStatus(200);
});

test('the products page loads successfully', function () {
    $response = $this->get('/products');

    $response->assertStatus(200);
});

test('the legacy projects route permanently redirects to products', function () {
    $response = $this->get('/projects');

    $response->assertRedirect('/products');
});

test('non-existent public resources render the 404 error page', function () {
    $response = $this->get('/resources/999999');

    $response->assertStatus(404);
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('errors/404'));
});

test('public resource page loads with subject, node, and enriched SEO metadata', function () {
    $subject = Subject::create([
        'name' => 'Higher Mathematics',
        'slug' => 'higher-math',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'calculator',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Matrix and Determinants',
        'slug' => 'matrix-and-determinants',
    ]);

    $resource = App\Models\Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Matrix o nirnayok 01',
        'status' => 'approved',
        'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
        'items' => [
            ['format' => 'video', 'title' => 'Matrix o nirnayok 01', 'source' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        ],
    ]);

    $response = $this->get("/resources/{$resource->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Resource')
        ->where('resource.id', $resource->id)
        ->where('subject.name', 'Higher Mathematics')
        ->where('subject.course', 'hsc')
    );

    $appName = config('app.name', 'HSCStack');
    $response->assertSee("Matrix o nirnayok 01 - HSC - Higher Mathematics - {$appName}", false);
    $response->assertSee('Study material: Matrix o nirnayok 01 (video) for HSC - Higher Mathematics on HSCStack.', false);
    $response->assertSee('<meta property="og:image" content="https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg">', false);
    $response->assertSee('<meta property="og:type" content="video.other">', false);

    // Ensure subsequent cache-hit request works properly without incomplete class errors
    $cachedResponse = $this->get("/resources/{$resource->id}");
    $cachedResponse->assertStatus(200);

    // Verify resource with custom thumbnail
    $thumbResource = App\Models\Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'note',
        'title' => 'Calculus Formula Sheet',
        'status' => 'approved',
        'thumbnail' => 'https://cdn.hscstack.site/thumbnails/calc.png',
        'items' => [
            ['format' => 'pdf', 'title' => 'Calculus PDF', 'source' => 'https://cdn.hscstack.site/docs/calc.pdf'],
        ],
    ]);

    $thumbResponse = $this->get("/resources/{$thumbResource->id}");
    $thumbResponse->assertStatus(200);
    $thumbResponse->assertSee('<meta property="og:image" content="https://cdn.hscstack.site/thumbnails/calc.png">', false);
});
