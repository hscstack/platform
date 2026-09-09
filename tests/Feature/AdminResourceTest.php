<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('edit resources', 'web');
    Permission::findOrCreate('create resources', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());
});

test('unauthorized users cannot bulk rename resources', function () {
    $user = User::factory()->create();
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
    ]);

    $this->actingAs($user)
        ->post("/admin/nodes/{$node->id}/resources/bulk-rename", [
            'prefix' => 'Class',
            'start_number' => 1,
        ])->assertRedirect();
});

test('admin with edit resources permission can bulk rename resources in sequential order', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $subject = Subject::create([
        'name' => 'Higher Math',
        'slug' => 'higher-math',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'calculator',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Matrix',
        'slug' => 'matrix',
    ]);

    $res1 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Old Title 1',
        'external_url' => 'https://youtube.com/watch?v=11111111111',
    ]);

    $res2 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Old Title 2',
        'external_url' => 'https://youtube.com/watch?v=22222222222',
    ]);

    $res3 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Old Title 3',
        'external_url' => 'https://youtube.com/watch?v=33333333333',
    ]);

    $this->actingAs($admin)
        ->post("/admin/nodes/{$node->id}/resources/bulk-rename", [
            'prefix' => 'Class - ',
            'start_number' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($res1->fresh()->title)->toBe('Class - 01');
    expect($res2->fresh()->title)->toBe('Class - 02');
    expect($res3->fresh()->title)->toBe('Class - 03');
});

test('bulk rename respects custom starting number', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $subject = Subject::create([
        'name' => 'Chemistry',
        'slug' => 'chemistry',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'flask',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Organic',
        'slug' => 'organic',
    ]);

    $res1 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Intro',
        'external_url' => 'https://youtube.com/watch?v=aaaaa',
    ]);

    $res2 = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'video',
        'title' => 'Part 2',
        'external_url' => 'https://youtube.com/watch?v=bbbbb',
    ]);

    $this->actingAs($admin)
        ->post("/admin/nodes/{$node->id}/resources/bulk-rename", [
            'prefix' => 'Lecture',
            'start_number' => 5,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($res1->fresh()->title)->toBe('Lecture - 05');
    expect($res2->fresh()->title)->toBe('Lecture - 06');
});

test('admin can import youtube playlist videos in normal and reversed order', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $subject = Subject::create([
        'name' => 'ICT',
        'slug' => 'ict',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'laptop',
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Networking',
        'slug' => 'networking',
    ]);

    Http::fake([
        'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
            'items' => [
                [
                    'snippet' => [
                        'title' => 'First Video',
                        'position' => 0,
                        'resourceId' => ['videoId' => 'vid11111111'],
                    ],
                ],
                [
                    'snippet' => [
                        'title' => 'Second Video',
                        'position' => 1,
                        'resourceId' => ['videoId' => 'vid22222222'],
                    ],
                ],
            ],
            'nextPageToken' => null,
        ], 200),
    ]);

    // Test reversed order with serial naming
    $this->actingAs($admin)
        ->post('/admin/resources/bulk/videos', [
            'node_id' => $node->id,
            'playlist_url' => 'https://www.youtube.com/playlist?list=PL123456789',
            'naming_strategy' => 'serial',
            'naming_prefix' => 'Video',
            'start_number' => 1,
            'is_reversed' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $resources = Resource::where('node_id', $node->id)->orderBy('id')->get();
    expect($resources)->toHaveCount(2);
    // In reversed order, Second Video comes first and gets serial number 01
    expect($resources[0]->title)->toBe('Video - 01');
    expect($resources[0]->external_url)->toBe('https://www.youtube.com/watch?v=vid22222222');
    expect($resources[1]->title)->toBe('Video - 02');
    expect($resources[1]->external_url)->toBe('https://www.youtube.com/watch?v=vid11111111');
});
