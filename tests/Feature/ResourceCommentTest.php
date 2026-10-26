<?php

use App\Models\Chapter;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\ResourceCommentMentionNotification;
use App\Notifications\ResourceCommentNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Permission::findOrCreate('moderate resources', 'web');

    $this->author = User::factory()->create(['username' => 'resource_author']);
    $this->subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
    ]);
    $this->node = Chapter::create([
        'subject_id' => $this->subject->id,
        'name' => 'Vectors',
    ]);
    $this->resource = Resource::create([
        'user_id' => $this->author->id,
        'subject_id' => $this->subject->id,
        'chapter_id' => $this->node->id,
        'resource_type' => 'note',
        'title' => 'Vector Formulas Handnote',
        'format' => 'image',
        'status' => 'approved',
        'items' => ['https://example.com/test.jpg'],
    ]);
});

test('guests are redirected when trying to comment on a resource', function () {
    $this->post("/resources/{$this->resource->id}/comments", [
        'content' => 'Great note!',
    ])->assertRedirect('/login');
});

test('authenticated user can post comment and notify resource author', function () {
    Notification::fake();

    $commenter = User::factory()->create(['name' => 'Commenter']);

    $response = $this->actingAs($commenter)
        ->post("/resources/{$this->resource->id}/comments", [
            'content' => 'This is an awesome resource, thank you!',
        ]);

    $response->assertSessionHas('success');
    expect($this->resource->comments()->count())->toBe(1);

    Notification::assertSentTo(
        $this->author,
        ResourceCommentNotification::class,
        fn ($n) => $n->resource->id === $this->resource->id
    );
});

test('replying to a comment creates 1-level deep reply', function () {
    Notification::fake();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $parentComment = $this->resource->comments()->create([
        'user_id' => $user1->id,
        'content' => 'First top-level comment',
    ]);

    $this->actingAs($user2)
        ->post("/resources/{$this->resource->id}/comments", [
            'content' => 'Replying to first comment',
            'parent_id' => $parentComment->id,
        ])
        ->assertSessionHas('success');

    expect($parentComment->replies()->count())->toBe(1);
    expect($parentComment->replies->first()->parent_id)->toBe($parentComment->id);
});

test('mentioning a user sends ResourceCommentMentionNotification', function () {
    Notification::fake();

    $mentioned = User::factory()->create(['username' => 'target_student']);
    $commenter = User::factory()->create(['username' => 'helpful_peer']);

    $this->actingAs($commenter)
        ->post("/resources/{$this->resource->id}/comments", [
            'content' => 'Check this out @target_student for exam prep!',
        ])
        ->assertSessionHas('success');

    Notification::assertSentTo(
        $mentioned,
        ResourceCommentMentionNotification::class,
        fn ($n) => $n->resource->id === $this->resource->id && $n->sender->id === $commenter->id
    );
});

test('user can delete their own comment and moderator can delete any comment', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->create();
    $moderator->givePermissionTo('moderate resources');

    $comment1 = $this->resource->comments()->create([
        'user_id' => $user->id,
        'content' => 'User comment',
    ]);

    $comment2 = $this->resource->comments()->create([
        'user_id' => $user->id,
        'content' => 'Another comment',
    ]);

    // User deletes own comment
    $this->actingAs($user)
        ->delete("/resources/comments/{$comment1->id}")
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('resource_comments', ['id' => $comment1->id]);

    // Moderator deletes user comment
    $this->actingAs($moderator)
        ->delete("/resources/comments/{$comment2->id}")
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('resource_comments', ['id' => $comment2->id]);
});

test('suspended user is blocked from posting resource comments', function () {
    $suspendedUser = User::factory()->create([
        'banned_until' => now()->addDays(3),
    ]);

    $this->actingAs($suspendedUser)
        ->post("/resources/{$this->resource->id}/comments", [
            'content' => 'Spam comment attempt',
        ])
        ->assertSessionHas('error');

    expect($this->resource->comments()->count())->toBe(0);
});
