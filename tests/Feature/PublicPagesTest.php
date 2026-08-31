<?php

use App\Models\Blog;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

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

test('the projects page loads successfully', function () {
    $response = $this->get('/projects');

    $response->assertStatus(200);
});

test('non-existent public resources render the 404 error page', function () {
    $response = $this->get('/resources/999999');

    $response->assertStatus(404);
    $response->assertSee('errors\/404');
});
