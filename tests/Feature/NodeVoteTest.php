<?php

use App\Mail\NodeNotificationMail;
use App\Models\Node;
use App\Models\NodeVote;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when attempting to vote on a folder', function () {
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

    $this->post("/nodes/{$node->id}/vote", ['type' => 'up'])
        ->assertRedirect('/login');

    $this->assertDatabaseMissing('node_votes', [
        'node_id' => $node->id,
    ]);
});

test('authenticated user can upvote a folder', function () {
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
        ->post("/nodes/{$node->id}/vote", ['type' => 'up'])
        ->assertRedirect();

    $this->assertDatabaseHas('node_votes', [
        'node_id' => $node->id,
        'user_id' => $user->id,
        'type' => 'up',
    ]);
});

test('upvoting an already upvoted folder removes the vote (toggle off)', function () {
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

    NodeVote::create([
        'node_id' => $node->id,
        'user_id' => $user->id,
        'type' => 'up',
    ]);

    $this->actingAs($user)
        ->post("/nodes/{$node->id}/vote", ['type' => 'up'])
        ->assertRedirect();

    $this->assertDatabaseMissing('node_votes', [
        'node_id' => $node->id,
        'user_id' => $user->id,
    ]);
});

test('authenticated user can downvote a folder and toggle it off', function () {
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

    // Downvote
    $this->actingAs($user)
        ->post("/nodes/{$node->id}/vote", ['type' => 'down'])
        ->assertRedirect();

    $this->assertDatabaseHas('node_votes', [
        'node_id' => $node->id,
        'user_id' => $user->id,
        'type' => 'down',
    ]);

    // Toggle off
    $this->actingAs($user)
        ->post("/nodes/{$node->id}/vote", ['type' => 'down'])
        ->assertRedirect();

    $this->assertDatabaseMissing('node_votes', [
        'node_id' => $node->id,
        'user_id' => $user->id,
    ]);
});

test('authenticated user switching vote between up and down updates type', function () {
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

    // Start with upvote
    $this->actingAs($user)
        ->post("/nodes/{$node->id}/vote", ['type' => 'up'])
        ->assertRedirect();

    $this->assertDatabaseHas('node_votes', [
        'node_id' => $node->id,
        'user_id' => $user->id,
        'type' => 'up',
    ]);

    // Switch to downvote
    $this->actingAs($user)
        ->post("/nodes/{$node->id}/vote", ['type' => 'down'])
        ->assertRedirect();

    $this->assertDatabaseHas('node_votes', [
        'node_id' => $node->id,
        'user_id' => $user->id,
        'type' => 'down',
    ]);
    $this->assertEquals(1, NodeVote::where('node_id', $node->id)->where('user_id', $user->id)->count());
});

test('top level chapters under subject are sorted strictly by sort_order', function () {
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $chapter1 = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1: Vectors',
        'slug' => 'chapter-1',
        'sort_order' => 1,
    ]);

    $chapter2 = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 2: Dynamics',
        'slug' => 'chapter-2',
        'sort_order' => 2,
    ]);

    $chapter3 = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 3: Energy',
        'slug' => 'chapter-3',
        'sort_order' => 3,
    ]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Chapter 3 has 2 upvotes, Chapter 1 has 0 upvotes
    NodeVote::create(['node_id' => $chapter3->id, 'user_id' => $user1->id, 'type' => 'up']);
    NodeVote::create(['node_id' => $chapter3->id, 'user_id' => $user2->id, 'type' => 'up']);

    // Subject page must still maintain chronological syllabus sequence by sort_order
    $response = $this->get('/physics');
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Node')
        ->has('nodes', 3)
        ->where('nodes.0.id', $chapter1->id)
        ->where('nodes.1.id', $chapter2->id)
        ->where('nodes.2.id', $chapter3->id)
    );
});

test('subfolders inside a chapter are sorted by net votes first then sort_order', function () {
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $chapter = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1',
        'slug' => 'chapter-1',
        'sort_order' => 1,
    ]);

    // Subfolders uploaded by different contributors inside Chapter 1
    $subA = Node::create([
        'subject_id' => $subject->id,
        'parent_id' => $chapter->id,
        'name' => 'Notes by Rahim (score +1)',
        'slug' => 'notes-by-rahim',
        'sort_order' => 0,
    ]);

    $subB = Node::create([
        'subject_id' => $subject->id,
        'parent_id' => $chapter->id,
        'name' => 'Notes by Karim (score +3)',
        'slug' => 'notes-by-karim',
        'sort_order' => 0,
    ]);

    $subC = Node::create([
        'subject_id' => $subject->id,
        'parent_id' => $chapter->id,
        'name' => 'Notes by Teacher (score -1)',
        'slug' => 'notes-by-teacher',
        'sort_order' => 0,
    ]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    // SubB gets 3 upvotes
    NodeVote::create(['node_id' => $subB->id, 'user_id' => $user1->id, 'type' => 'up']);
    NodeVote::create(['node_id' => $subB->id, 'user_id' => $user2->id, 'type' => 'up']);
    NodeVote::create(['node_id' => $subB->id, 'user_id' => $user3->id, 'type' => 'up']);

    // SubA gets 1 upvote
    NodeVote::create(['node_id' => $subA->id, 'user_id' => $user1->id, 'type' => 'up']);

    // SubC gets 1 downvote
    NodeVote::create(['node_id' => $subC->id, 'user_id' => $user1->id, 'type' => 'down']);

    // Inside Chapter 1, subfolders rank by net votes: SubB (+3) -> SubA (+1) -> SubC (-1)
    $response = $this->get('/physics/chapter-1');
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Node')
        ->has('nodes', 3)
        ->where('nodes.0.id', $subB->id)
        ->where('nodes.1.id', $subA->id)
        ->where('nodes.2.id', $subC->id)
    );
});

test('node page shows upvote list and counts while downvote list is not exposed', function () {
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

    $upvoter = User::factory()->create(['name' => 'Upvoter User']);
    $downvoter = User::factory()->create(['name' => 'Downvoter Secret User']);

    NodeVote::create(['node_id' => $node->id, 'user_id' => $upvoter->id, 'type' => 'up']);
    NodeVote::create(['node_id' => $node->id, 'user_id' => $downvoter->id, 'type' => 'down']);

    $response = $this->actingAs($upvoter)->get('/physics/chapter-1');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Node')
        ->where('upvotesCount', 1)
        ->where('downvotesCount', 1)
        ->where('userVote', 'up')
        ->has('upvoters', 1)
        ->where('upvoters.0.id', $upvoter->id)
        ->where('upvoters.0.name', 'Upvoter User')
        ->missing('downvoters')
    );
});

test('voting on a folder properly clears and refreshes parent node and subject page caches', function () {
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $folderA = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Folder A',
        'slug' => 'folder-a',
        'sort_order' => 1,
    ]);

    $folderB = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Folder B',
        'slug' => 'folder-b',
        'sort_order' => 2,
    ]);

    $user = User::factory()->create();

    // 1. Visit subject page to warm the cache
    $this->get('/physics')
        ->assertInertia(fn (Assert $page) => $page
            ->where('nodes.0.upvotes_count', 0)
            ->where('nodes.1.upvotes_count', 0)
        );

    expect(Cache::has("subject_page_{$subject->id}"))->toBeTrue();

    // 2. User votes on Folder B
    $this->actingAs($user)
        ->post("/nodes/{$folderB->id}/vote", ['type' => 'up'])
        ->assertRedirect();

    // Cache should have been invalidated
    expect(Cache::has("subject_page_{$subject->id}"))->toBeFalse();

    // 3. Next visit to subject page reflects Folder B with updated upvote count
    $this->get('/physics')
        ->assertInertia(fn (Assert $page) => $page
            ->where('nodes.1.id', $folderB->id)
            ->where('nodes.1.upvotes_count', 1)
        );

    // 4. Test nested child node caching
    $subfolderA = Node::create([
        'subject_id' => $subject->id,
        'parent_id' => $folderA->id,
        'name' => 'Subfolder A',
        'slug' => 'subfolder-a',
        'sort_order' => 0,
    ]);

    $subfolderB = Node::create([
        'subject_id' => $subject->id,
        'parent_id' => $folderA->id,
        'name' => 'Subfolder B',
        'slug' => 'subfolder-b',
        'sort_order' => 0,
    ]);

    // Warm child cache
    $this->get("/physics/{$folderA->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('nodes.0.upvotes_count', 0)
            ->where('nodes.1.upvotes_count', 0)
        );

    expect(Cache::has("node_children_{$folderA->id}"))->toBeTrue();

    // Vote on Subfolder B
    $this->actingAs($user)
        ->post("/nodes/{$subfolderB->id}/vote", ['type' => 'up'])
        ->assertRedirect();

    // Cache should be cleared
    expect(Cache::has("node_children_{$folderA->id}"))->toBeFalse();

    // Next visit reflects new order and count
    $this->get("/physics/{$folderA->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('nodes.0.id', $subfolderB->id)
            ->where('nodes.0.upvotes_count', 1)
        );
});

test('folder author receives milestone email notification when folder hits 1 upvote', function () {
    Mail::fake();

    $author = User::factory()->create([
        'name' => 'Author Rahim',
        'email' => 'rahim@example.com',
        'receive_emails' => true,
    ]);

    $voter = User::factory()->create([
        'name' => 'Voter Karim',
        'email' => 'karim@example.com',
    ]);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $folder = Node::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'name' => 'Vectors Complete Guide',
        'slug' => 'vectors-guide',
    ]);

    // Voter upvotes folder
    $this->actingAs($voter)
        ->post("/nodes/{$folder->id}/vote", ['type' => 'up'])
        ->assertRedirect();

    Mail::assertQueued(NodeNotificationMail::class, function ($mail) use ($author) {
        return $mail->hasTo($author->email) &&
            str_contains($mail->mailSubject, 'first upvote') &&
            str_contains($mail->mailContent, 'Vectors Complete Guide');
    });
});

test('author upvoting their own folder does not trigger milestone email', function () {
    Mail::fake();

    $author = User::factory()->create([
        'name' => 'Self Voter',
        'email' => 'self@example.com',
        'receive_emails' => true,
    ]);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    $folder = Node::create([
        'user_id' => $author->id,
        'subject_id' => $subject->id,
        'name' => 'Vectors Complete Guide',
        'slug' => 'vectors-guide',
    ]);

    $this->actingAs($author)
        ->post("/nodes/{$folder->id}/vote", ['type' => 'up'])
        ->assertRedirect();

    Mail::assertNothingQueued();
});

test('first resource upload in an empty node automatically updates node user_id to the contributor', function () {
    $admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);

    $contributor = User::factory()->create([
        'name' => 'Contributor Rahim',
        'email' => 'rahim@example.com',
        'receive_emails' => true,
    ]);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);

    // Admin pre-creates an empty folder skeleton
    $folder = Node::create([
        'user_id' => $admin->id,
        'subject_id' => $subject->id,
        'name' => 'Chapter 1 Skeleton',
        'slug' => 'chapter-1-skeleton',
    ]);

    expect($folder->user_id)->toBe($admin->id);

    // Contributor uploads the first resource
    App\Models\Resource::create([
        'node_id' => $folder->id,
        'user_id' => $contributor->id,
        'title' => 'Lecture Note PDF',
        'resource_type' => 'note',
    ]);

    // Node owner should now be automatically updated to the contributor
    $folder->refresh();
    expect($folder->user_id)->toBe($contributor->id);

    // Upvoting milestone should route to the contributor
    Mail::fake();
    $voter = User::factory()->create();

    $this->actingAs($voter)
        ->post("/nodes/{$folder->id}/vote", ['type' => 'up'])
        ->assertRedirect();

    Mail::assertQueued(NodeNotificationMail::class, function ($mail) use ($contributor) {
        return $mail->hasTo($contributor->email);
    });
});
