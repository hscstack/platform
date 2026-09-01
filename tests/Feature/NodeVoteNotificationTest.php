<?php

use App\Models\Node;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\NodeVoteNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('upvoting a study node sends NodeVoteNotification to node contributor', function () {
    Notification::fake();

    $author = User::factory()->create();
    $voter = User::factory()->create(['name' => 'Diligent Student']);
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-node',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $node = Node::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'name' => 'Vectors and Kinematics',
        'slug' => 'vectors-and-kinematics',
        'sort_order' => 1,
    ]);

    $this->actingAs($voter)->post(route('nodes.vote', $node->id), [
        'type' => 'up',
    ]);

    Notification::assertSentTo($author, NodeVoteNotification::class, function ($notification) use ($node, $voter) {
        return $notification->node->id === $node->id
            && $notification->voter->id === $voter->id;
    });
});

test('downvoting a study node does not trigger NodeVoteNotification', function () {
    Notification::fake();

    $author = User::factory()->create();
    $voter = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-node-down',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $node = Node::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'name' => 'Vectors and Kinematics',
        'slug' => 'vectors-and-kinematics',
        'sort_order' => 1,
    ]);

    $this->actingAs($voter)->post(route('nodes.vote', $node->id), [
        'type' => 'down',
    ]);

    Notification::assertNothingSent();
});

test('author upvoting own study node does not trigger NodeVoteNotification', function () {
    Notification::fake();

    $author = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-node-self',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $node = Node::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'name' => 'Vectors and Kinematics',
        'slug' => 'vectors-and-kinematics',
        'sort_order' => 1,
    ]);

    $this->actingAs($author)->post(route('nodes.vote', $node->id), [
        'type' => 'up',
    ]);

    Notification::assertNothingSent();
});

test('repeatedly toggling node vote does not trigger spam notifications', function () {
    Notification::fake();

    $author = User::factory()->create();
    $voter = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-node-spam',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $node = Node::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'name' => 'Vectors and Kinematics',
        'slug' => 'vectors-and-kinematics',
        'sort_order' => 1,
    ]);

    // 1st initial upvote -> sends notification
    $this->actingAs($voter)->post(route('nodes.vote', $node->id), ['type' => 'up']);
    Notification::assertSentToTimes($author, NodeVoteNotification::class, 1);

    // 2nd toggle to downvote -> NO notification
    $this->actingAs($voter)->post(route('nodes.vote', $node->id), ['type' => 'down']);
    Notification::assertSentToTimes($author, NodeVoteNotification::class, 1);

    // 3rd toggle back to upvote -> NO notification
    $this->actingAs($voter)->post(route('nodes.vote', $node->id), ['type' => 'up']);
    Notification::assertSentToTimes($author, NodeVoteNotification::class, 1);
});

test('NodeVoteNotification is strictly database only', function () {
    $author = User::factory()->create(['receive_emails' => true]);
    $voter = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc-node-db',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $node = Node::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'name' => 'Vectors and Kinematics',
        'slug' => 'vectors-and-kinematics',
        'sort_order' => 1,
    ]);

    $notif = new NodeVoteNotification($node, $voter);
    expect($notif->via($author))->toBe(['database']);
});
