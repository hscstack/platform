<?php

use App\Models\Node;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;

test('guests are redirected when attempting to toggle completion', function () {
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
    $resource = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'image',
        'title' => 'Vectors Handnote',
        'file_path' => 'resources/sample.jpg',
    ]);

    $this->post("/resources/{$resource->id}/complete")
        ->assertRedirect('/login');
});

test('authenticated user can toggle resource completion status', function () {
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
    $resource = Resource::create([
        'node_id' => $node->id,
        'resource_type' => 'image',
        'title' => 'Vectors Handnote',
        'file_path' => 'resources/sample.jpg',
    ]);

    // Mark as done
    $this->actingAs($user)
        ->post("/resources/{$resource->id}/complete")
        ->assertRedirect();

    $this->assertDatabaseHas('resource_completions', [
        'resource_id' => $resource->id,
        'user_id' => $user->id,
    ]);

    // Undo / Unmark
    $this->actingAs($user)
        ->post("/resources/{$resource->id}/complete")
        ->assertRedirect();

    $this->assertDatabaseMissing('resource_completions', [
        'resource_id' => $resource->id,
        'user_id' => $user->id,
    ]);
});
