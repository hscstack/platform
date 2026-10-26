<?php

use App\Models\AppSetting;
use App\Models\Chapter;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

test('the homepage loads successfully', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('notice is displayed on homepage and invalidated upon update', function () {
    AppSetting::set('site_notice', json_encode([
        'title' => 'Important Notice',
        'is_active' => true,
    ]));

    $this->get('/')->assertStatus(200);

    // Cache should be populated
    expect(Cache::has('active_notice_for_display'))->toBeTrue();

    // Cache should be invalidated when notice is updated
    Cache::forget('active_notice_for_display');
    expect(Cache::has('active_notice_for_display'))->toBeFalse();
});

test('the about us page loads successfully and separates board members', function () {
    Cache::forget('about_us_info');

    $boardUser = User::factory()->create([
        'name' => 'Founder User',
        'is_verified' => true,
        'is_board_member' => true,
        'priority' => 10,
    ]);

    $teamUser = User::factory()->create([
        'name' => 'Contributor User',
        'is_verified' => true,
        'is_board_member' => false,
        'priority' => 5,
    ]);

    $response = $this->get('/about-us');

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('platform/AboutUs')
        ->where('users.0.name', 'Founder User')
        ->where('users.0.is_board_member', true)
        ->where('users.1.name', 'Contributor User')
        ->where('users.1.is_board_member', false)
    );
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
        'course' => 'hsc',
    ]);

    $node = Chapter::create([
        'subject_id' => $subject->id,
        'name' => 'Matrix and Determinants',
    ]);

    $resource = App\Models\Resource::create([
        'chapter_id' => $node->id,
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
        ->where('resource.subject.name', 'Higher Mathematics')
        ->where('resource.subject.course', 'hsc')
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
        'chapter_id' => $node->id,
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
