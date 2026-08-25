<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('unauthenticated users cannot generate short urls', function () {
    $response = $this->postJson(route('short-urls.store'), [
        'original_url' => 'https://hscstack.com/physics/chapter-1',
    ]);

    $response->assertStatus(401);
});

test('authenticated user can generate short url via short.io', function () {
    $user = User::factory()->create();

    config([
        'services.short_io.api_key' => 'test-api-key',
        'services.short_io.domain' => 'go.hscstack.com',
    ]);

    Http::fake([
        'https://api.short.io/links' => Http::response([
            'id' => 12345,
            'idString' => 'lnk_abc123',
            'originalURL' => 'https://hscstack.com/physics/chapter-1',
            'shortURL' => 'https://go.hscstack.com/phy1',
            'secureShortURL' => 'https://go.hscstack.com/phy1',
            'path' => 'phy1',
            'duplicate' => false,
        ], 200),
    ]);

    $response = $this->actingAs($user)->postJson(route('short-urls.store'), [
        'original_url' => 'https://hscstack.com/physics/chapter-1',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'short_url' => 'https://go.hscstack.com/phy1',
        ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.short.io/links' &&
            $request['originalURL'] === 'https://hscstack.com/physics/chapter-1' &&
            $request['domain'] === 'go.hscstack.com' &&
            $request['allowDuplicates'] === false &&
            $request->hasHeader('Authorization', 'test-api-key');
    });
});

test('validates original_url is a valid url', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('short-urls.store'), [
        'original_url' => 'not-a-valid-url',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['original_url']);
});

test('handles short.io api errors gracefully', function () {
    $user = User::factory()->create();

    config([
        'services.short_io.api_key' => 'test-api-key',
        'services.short_io.domain' => 'go.hscstack.com',
    ]);

    Http::fake([
        'https://api.short.io/links' => Http::response([
            'message' => 'Domain not found or unauthorized',
        ], 400),
    ]);

    $response = $this->actingAs($user)->postJson(route('short-urls.store'), [
        'original_url' => 'https://hscstack.com/biology/genetics',
    ]);

    $response->assertStatus(500)
        ->assertJson([
            'message' => 'Domain not found or unauthorized',
        ]);
});
