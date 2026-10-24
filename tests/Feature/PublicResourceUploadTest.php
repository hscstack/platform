<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('s3');
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('moderate resources', 'web');
    Role::findOrCreate('admin', 'web');
});

function createTestSubjectAndNode(bool $isFrozen = false): array
{
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1',
        'slug' => 'chapter-1',
        'is_frozen' => $isFrozen,
    ]);

    return [$subject, $node];
}

test('guest cannot upload documents', function () {
    [$subject, $node] = createTestSubjectAndNode();

    $this->post('/resources', [
        'subject_id' => $subject->id,
        'node_id' => $node->id,
        'resource_type' => 'note',
        'title' => 'Test Note',
        'items' => [
            ['format' => 'image', 'source' => 'resources/notes/test.webp'],
        ],
    ])->assertRedirect('/login');
});

test('banned user cannot upload resources', function () {
    [$subject, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'banned_until' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Banned User Note',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/test.webp'],
            ],
        ]);

    $response->assertSessionHasErrors('banned');
    expect(Resource::count())->toBe(0);
});

test('public authenticated user submits document with pending status', function () {
    [$subject, $node] = createTestSubjectAndNode();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'My Community Note',
            'description' => 'Detailed notes',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/uuid-1.webp'],
                ['format' => 'image', 'source' => 'resources/notes/uuid-2.webp'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Resource::count())->toBe(1);

    $res = Resource::first();
    expect($res->user_id)->toBe($user->id)
        ->and($res->subject_id)->toBe($subject->id)
        ->and($res->node_id)->toBe($node->id)
        ->and($res->status)->toBe('pending')
        ->and($res->title)->toBe('My Community Note')
        ->and(count($res->items))->toBe(2);
});

test('moderator user uploading document gets auto-approved', function () {
    [$subject, $node] = createTestSubjectAndNode();

    $moderator = User::factory()->create();
    $moderator->givePermissionTo('moderate resources');

    $this->actingAs($moderator)
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Moderator Note',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/mod.webp'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $res = Resource::first();
    expect($res->status)->toBe('approved')
        ->and($res->reviewed_by)->toBe($moderator->id)
        ->and($res->reviewed_at)->not->toBeNull();
});

test('regular users are blocked after 20 pending submissions while verified can submit up to 50', function () {
    [$subject, $node] = createTestSubjectAndNode();

    $user = User::factory()->create([
        'is_verified' => false,
    ]);

    for ($i = 0; $i < 20; $i++) {
        Resource::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => "Pending Note {$i}",
            'status' => 'pending',
        ]);
    }

    // 21st submission should be rejected
    $this->actingAs($user)
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Exceeding Quota',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/extra.webp'],
            ],
        ])
        ->assertSessionHasErrors('pending_limit');

    // Make user verified - now limit is 50
    $user->update(['is_verified' => true]);

    $this->actingAs($user->fresh())
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Allowed For Verified',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/allowed.webp'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('cannot submit resource with mixed formats', function () {
    [$subject, $node] = createTestSubjectAndNode();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Mixed Formats Note',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/page1.webp'],
                ['format' => 'pdf', 'source' => 'https://drive.google.com/file/d/xyz/view'],
            ],
        ])
        ->assertSessionHasErrors('items');

    expect(Resource::count())->toBe(0);
});

test('cannot submit video resource with non-video formats or note resource with video format', function () {
    [$subject, $node] = createTestSubjectAndNode();
    $user = User::factory()->create();

    // 1. Video resource cannot contain image
    $this->actingAs($user)
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'video',
            'title' => 'Video with Image',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/page1.webp'],
            ],
        ])
        ->assertSessionHasErrors('items');

    // 2. Note resource cannot contain video
    $this->actingAs($user)
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'note',
            'title' => 'Note with Video',
            'items' => [
                ['format' => 'video', 'source' => 'https://www.youtube.com/watch?v=12345678901'],
            ],
        ])
        ->assertSessionHasErrors('items');

    expect(Resource::count())->toBe(0);
});

test('fetch playlist endpoint validates input and returns videos', function () {
    $user = User::factory()->create();

    // Invalid URL
    $this->actingAs($user)
        ->postJson('/resources/fetch-playlist', [
            'playlist_url' => 'not-a-url',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('playlist_url');

    // Valid URL with Http fake
    config(['services.youtube.key' => 'fake-key']);
    Http::fake([
        'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
            'items' => [
                [
                    'snippet' => [
                        'title' => 'Lecture 1: Intro',
                        'resourceId' => ['videoId' => 'vid11111111'],
                    ],
                ],
                [
                    'snippet' => [
                        'title' => 'Lecture 2: Advanced',
                        'resourceId' => ['videoId' => 'vid22222222'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->postJson('/resources/fetch-playlist', [
            'playlist_url' => 'https://www.youtube.com/playlist?list=PL1234567890',
        ]);

    $response->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.format', 'video')
        ->assertJsonPath('items.0.title', 'Lecture 1: Intro')
        ->assertJsonPath('items.0.source', 'https://www.youtube.com/watch?v=vid11111111');
});

test('fetch playlist endpoint is rate limited to 10 requests per minute', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($user)
            ->postJson('/resources/fetch-playlist', [
                'playlist_url' => 'https://www.youtube.com/playlist?list=PL1234567890',
            ]);
    }

    // 11th request should be throttled (HTTP 429)
    $this->actingAs($user)
        ->postJson('/resources/fetch-playlist', [
            'playlist_url' => 'https://www.youtube.com/playlist?list=PL1234567890',
        ])
        ->assertStatus(429);
});

test('user can submit marked_book resource with images', function () {
    [$subject, $node] = createTestSubjectAndNode();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/resources', [
            'subject_id' => $subject->id,
            'node_id' => $node->id,
            'resource_type' => 'marked_book',
            'title' => 'Physics 1st Paper Marked Book',
            'items' => [
                ['format' => 'image', 'source' => 'resources/notes/book_p1.webp'],
                ['format' => 'image', 'source' => 'resources/notes/book_p2.webp'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $res = Resource::where('resource_type', 'marked_book')->first();
    expect($res)->not->toBeNull()
        ->and($res->title)->toBe('Physics 1st Paper Marked Book')
        ->and(count($res->items))->toBe(2);
});
