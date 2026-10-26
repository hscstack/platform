<?php

use App\Models\AppSetting;
use App\Models\Chapter;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('view admin', 'web');
    Permission::findOrCreate('manage forums', 'web');
});

test('unauthenticated users can view forum index and show', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-hsc',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $post = ForumPost::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'I am struggling with relative velocity in vector physics.',
    ]);

    $response = $this->get('/forum');
    $response->assertOk();

    $post->votes()->create(['user_id' => $user->id, 'value' => 1]);

    $showResponse = $this->get("/forum/questions/{$post->slug}");
    $showResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Forum/Show')
            ->has('upvoters', 1)
        );
});

test('guest is redirected when creating a question', function () {
    $guestResponse = $this->post('/forum', [
        'body' => 'How to solve this problem?',
        'curriculum' => 'hsc',
    ]);
    $guestResponse->assertRedirect('/login');
});

test('authenticated user can create a question', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Higher Mathematics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'slug' => 'higher-math-hsc',
        'icon' => 'calculator',
        'sort_order' => 1,
    ]);
    $node = Chapter::create([
        'subject_id' => $subject->id,
        'user_id' => $user->id,
        'name' => 'Integration',
    ]);

    $response = $this->actingAs($user)->post('/forum', [
        'body' => 'Need help with Calculus Integration. How do I evaluate trigonometric substitution integrals?',
        'curriculum' => 'hsc',
        'subject_id' => $subject->id,
        'node_id' => $node->id,
    ]);

    $post = ForumPost::first();
    expect($post)->not->toBeNull()
        ->and($post->body)->toBe('Need help with Calculus Integration. How do I evaluate trigonometric substitution integrals?')
        ->and($post->subject_id)->toBe($subject->id)
        ->and($post->node_id)->toBe($node->id)
        ->and($post->user_id)->toBe($user->id);

    $response->assertRedirect("/forum/questions/{$post->slug}");
});

test('authenticated user can create a general question with null subject and chapter', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/forum', [
        'body' => 'Which engineering college has the best campus life? I am confused between multiple engineering universities and need student feedback.',
        'curriculum' => 'hsc',
        'subject_id' => null,
        'node_id' => null,
    ]);

    $post = ForumPost::latest('id')->first();
    expect($post)->not->toBeNull()
        ->and($post->body)->toBe('Which engineering college has the best campus life? I am confused between multiple engineering universities and need student feedback.')
        ->and($post->subject_id)->toBeNull()
        ->and($post->node_id)->toBeNull();

    $response->assertRedirect("/forum/questions/{$post->slug}");
});

test('authenticated user cannot create a question with empty body', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/forum', [
        'body' => '',
        'curriculum' => 'hsc',
    ]);

    $response->assertSessionHasErrors('body');
    expect(ForumPost::count())->toBe(0);
});

test('user can post answer and reply with 1-level nesting enforced', function () {
    $author = User::factory()->create();
    $replier = User::factory()->create();

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'What is the oxidation state of Chromium in K2Cr2O7?',
    ]);

    // Post direct answer
    $this->actingAs($replier)->post("/forum/posts/{$post->id}/answers", [
        'body' => 'The oxidation state of Cr is +6.',
    ]);

    $post->refresh();
    expect($post->answers_count)->toBe(1);

    $answer = ForumAnswer::whereNull('parent_id')->first();
    expect($answer)->not->toBeNull()
        ->and($answer->body)->toBe('The oxidation state of Cr is +6.');

    // Reply to direct answer
    $this->actingAs($author)->post("/forum/posts/{$post->id}/answers", [
        'parent_id' => $answer->id,
        'body' => 'Thanks for the quick response!',
    ]);

    $reply = ForumAnswer::whereNotNull('parent_id')->first();
    expect($reply->parent_id)->toBe($answer->id);

    // Reply to a reply -> should be flattened to root parent
    $anotherUser = User::factory()->create();
    $this->actingAs($anotherUser)->post("/forum/posts/{$post->id}/answers", [
        'parent_id' => $reply->id,
        'body' => 'Glad to see you understood it.',
    ]);

    $secondReply = ForumAnswer::where('user_id', $anotherUser->id)->first();
    expect($secondReply->parent_id)->toBe($answer->id); // Flattened to root!

    $post->refresh();
    expect($post->answers_count)->toBe(3);
});

test('voting on posts updates vote_score and toggles', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $post = ForumPost::create([
        'user_id' => $user1->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Sample body content for voting test.',
    ]);

    // Upvote by user 1
    $this->actingAs($user1)->post("/forum/posts/{$post->id}/vote", ['value' => 1]);
    $post->refresh();
    expect($post->vote_score)->toBe(1)
        ->and($post->upvotes_count)->toBe(1)
        ->and($post->downvotes_count)->toBe(0);

    // Upvote by user 2
    $this->actingAs($user2)->post("/forum/posts/{$post->id}/vote", ['value' => 1]);
    $post->refresh();
    expect($post->vote_score)->toBe(2)
        ->and($post->upvotes_count)->toBe(2)
        ->and($post->downvotes_count)->toBe(0);

    // Switch user 2 to downvote
    $this->actingAs($user2)->post("/forum/posts/{$post->id}/vote", ['value' => -1]);
    $post->refresh();
    expect($post->vote_score)->toBe(0)
        ->and($post->upvotes_count)->toBe(1)
        ->and($post->downvotes_count)->toBe(1);

    // Toggle off user 1 upvote
    $this->actingAs($user1)->post("/forum/posts/{$post->id}/vote", ['value' => 1]);
    $post->refresh();
    expect($post->vote_score)->toBe(-1)
        ->and($post->upvotes_count)->toBe(0)
        ->and($post->downvotes_count)->toBe(1);
});

test('profane words trigger CleanText validation on post and answer', function () {
    AppSetting::set('global_chat_banned_words', 'fuck,badword');
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'physics-profane-test',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($user)->post('/forum', [
        'body' => 'Sample body with fuck word inside.',
        'curriculum' => 'hsc',
        'subject_id' => $subject->id,
    ]);
    $response->assertSessionHasErrors('body');

    $post = ForumPost::create([
        'user_id' => $user->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Clean Question Body',
    ]);

    $answerResponse = $this->actingAs($user)->post("/forum/posts/{$post->id}/answers", [
        'body' => 'Answer with badword inside.',
    ]);
    $answerResponse->assertSessionHasErrors('body');
});

test('author can delete their own posts and answers while others cannot', function () {
    $author = User::factory()->create();
    $replier = User::factory()->create();
    $other = User::factory()->create();

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Post body to be deleted.',
    ]);

    $this->actingAs($replier)->post("/forum/posts/{$post->id}/answers", [
        'body' => 'Answer to be deleted.',
    ]);
    $answer = ForumAnswer::first();

    // Other user cannot delete answer
    $this->actingAs($other)->delete("/forum/answers/{$answer->id}")
        ->assertForbidden();

    // Replier can delete their own answer
    $this->actingAs($replier)->delete("/forum/answers/{$answer->id}");
    expect(ForumAnswer::count())->toBe(0);

    // Other user cannot delete post
    $this->actingAs($other)->delete("/forum/posts/{$post->id}")
        ->assertForbidden();

    // Post author can delete post
    $this->actingAs($author)->delete("/forum/posts/{$post->id}")
        ->assertRedirect('/forum');
    expect(ForumPost::count())->toBe(0);
});

test('voting on answers works correctly', function () {
    $author = User::factory()->create();
    $voter = User::factory()->create();

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Post body.',
    ]);

    $answer = $post->answers()->create([
        'user_id' => $author->id,
        'body' => 'Answer to be voted on.',
    ]);

    $this->actingAs($voter)->post("/forum/answers/{$answer->id}/vote", ['value' => 1]);
    $answer->refresh();
    expect($answer->vote_score)->toBe(1);

    $this->actingAs($voter)->post("/forum/answers/{$answer->id}/vote", ['value' => 1]);
    $answer->refresh();
    expect($answer->vote_score)->toBe(0);
});

test('invalid subject course or mismatched node resets invalid fields on store', function () {
    $user = User::factory()->create();
    $hscSubject = Subject::create([
        'name' => 'HSC Physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'slug' => 'hsc-physics-tax',
        'icon' => 'atom',
        'sort_order' => 1,
    ]);
    $sscSubject = Subject::create([
        'name' => 'SSC Math',
        'course' => 'ssc',
        'tailwind_format' => 'bg-emerald-500',
        'slug' => 'ssc-math-tax',
        'icon' => 'calculator',
        'sort_order' => 2,
    ]);
    $sscNode = Chapter::create([
        'subject_id' => $sscSubject->id,
        'name' => 'Algebra Chapter',
        'sort_order' => 1,
    ]);

    // Post with HSC curriculum but SSC subject -> subject_id and node_id should be nullified
    $this->actingAs($user)->post('/forum', [
        'curriculum' => 'hsc',
        'subject_id' => $sscSubject->id,
        'node_id' => $sscNode->id,
        'body' => 'Taxonomy Mismatch Question Checking taxonomy boundary conditions.',
    ]);

    $post = ForumPost::where('body', 'Taxonomy Mismatch Question Checking taxonomy boundary conditions.')->first();
    expect($post)->not->toBeNull()
        ->and($post->subject_id)->toBeNull()
        ->and($post->node_id)->toBeNull();

    // Post with HSC subject but mismatched SSC node -> node_id should be nullified
    $this->actingAs($user)->post('/forum', [
        'curriculum' => 'hsc',
        'subject_id' => $hscSubject->id,
        'node_id' => $sscNode->id,
        'body' => 'Mismatched Node Question Checking node subject boundary conditions.',
    ]);

    $post2 = ForumPost::where('body', 'Mismatched Node Question Checking node subject boundary conditions.')->first();
    expect($post2)->not->toBeNull()
        ->and($post2->subject_id)->toBe($hscSubject->id)
        ->and($post2->node_id)->toBeNull();
});

test('deleting a post cleans up all child answer images from storage', function () {
    Storage::fake();

    $author = User::factory()->create();
    $replier = User::factory()->create();

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Post with Image Answers Testing image cleanup.',
        'images' => ['forum/posts/test_post.png'],
    ]);
    Storage::put('forum/posts/test_post.png', 'fake post image');

    $answer = $post->answers()->create([
        'user_id' => $replier->id,
        'body' => 'Answer with an image.',
        'image_path' => 'forum/answers/test_answer.png',
    ]);
    Storage::put('forum/answers/test_answer.png', 'fake answer image');

    expect(Storage::exists('forum/posts/test_post.png'))->toBeTrue()
        ->and(Storage::exists('forum/answers/test_answer.png'))->toBeTrue();

    $this->actingAs($author)->delete("/forum/posts/{$post->id}");

    expect(Storage::exists('forum/posts/test_post.png'))->toBeFalse()
        ->and(Storage::exists('forum/answers/test_answer.png'))->toBeFalse();
});

test('unauthenticated users filtering by my_posts receive zero questions', function () {
    $user = User::factory()->create();
    ForumPost::create([
        'user_id' => $user->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Content of the question for guest filter test.',
    ]);

    $response = $this->get('/forum?my_posts=1');
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Forum/Index')
            ->has('posts.data', 0)
        );
});

test('users cannot reply to unapproved discussions', function () {
    $author = User::factory()->create();
    $regularUser = User::factory()->create();
    $moderator = User::factory()->create();
    $moderator->givePermissionTo('manage forums');

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Body of unpublished question.',
        'moderation_status' => 'rejected',
    ]);

    // Regular user cannot reply
    $this->actingAs($regularUser)
        ->post("/forum/posts/{$post->id}/answers", [
            'body' => 'Attempting reply to unpublished post.',
        ])
        ->assertSessionHas('error');

    expect(ForumAnswer::where('forum_post_id', $post->id)->count())->toBe(0);

    // Moderator also cannot reply while unapproved
    $this->actingAs($moderator)
        ->post("/forum/posts/{$post->id}/answers", [
            'body' => 'Moderator administrative reply.',
        ])
        ->assertSessionHas('error');

    expect(ForumAnswer::where('forum_post_id', $post->id)->count())->toBe(0);
});

test('submitting question with auto approval mode sets moderation_status to approved', function () {
    AppSetting::set('forum_approval_mode', 'auto');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('forum.store'), [
            'body' => 'Body of auto approved question with enough details.',
            'curriculum' => 'hsc',
        ])
        ->assertRedirect();

    $post = ForumPost::where('body', 'Body of auto approved question with enough details.')->first();
    expect($post)->not->toBeNull();
    expect($post->moderation_status)->toBe('approved');
});

test('submitting question with manual approval mode sets moderation_status to pending', function () {
    AppSetting::set('forum_approval_mode', 'manual');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('forum.store'), [
            'body' => 'Body of manual pending question with enough details.',
            'curriculum' => 'ssc',
        ])
        ->assertRedirect();

    $post = ForumPost::where('body', 'Body of manual pending question with enough details.')->first();
    expect($post)->not->toBeNull();
    expect($post->moderation_status)->toBe('pending');
});

test('author and admin can view pending or flagged questions but other users get 404', function () {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->create();
    $admin->givePermissionTo('manage forums');

    $pendingPost = ForumPost::factory()->create([
        'user_id' => $author->id,
        'moderation_status' => 'pending',
    ]);

    $flaggedPost = ForumPost::factory()->create([
        'user_id' => $author->id,
        'moderation_status' => 'flagged',
    ]);

    // Author can view
    $this->actingAs($author)->get(route('forum.show', $pendingPost))->assertStatus(200);
    $this->actingAs($author)->get(route('forum.show', $flaggedPost))->assertStatus(200);

    // Admin can view
    $this->actingAs($admin)->get(route('forum.show', $pendingPost))->assertStatus(200);
    $this->actingAs($admin)->get(route('forum.show', $flaggedPost))->assertStatus(200);

    // Other user gets 404
    $this->actingAs($otherUser)->get(route('forum.show', $pendingPost))->assertInertia(fn ($page) => $page->component('errors/404'));
    $this->actingAs($otherUser)->get(route('forum.show', $flaggedPost))->assertInertia(fn ($page) => $page->component('errors/404'));

    // Guest gets 404
    $this->get(route('forum.show', $pendingPost))->assertInertia(fn ($page) => $page->component('errors/404'));
});

test('suspended user cannot create forum questions', function () {
    $suspendedUser = User::factory()->create([
        'banned_until' => now()->addDays(2),
    ]);

    $this->actingAs($suspendedUser)
        ->post('/forum', [
            'body' => 'Suspended user question body content here.',
            'curriculum' => 'hsc',
        ])
        ->assertSessionHas('error');

    expect(ForumPost::where('user_id', $suspendedUser->id)->count())->toBe(0);
});

test('suspended user cannot reply to forum questions', function () {
    $author = User::factory()->create();
    $suspendedUser = User::factory()->create([
        'banned_until' => now()->addDays(2),
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Active forum question body content.',
    ]);

    $this->actingAs($suspendedUser)
        ->post("/forum/posts/{$post->id}/answers", [
            'body' => 'Attempted answer by suspended user.',
        ])
        ->assertSessionHas('error');

    expect(ForumAnswer::where('user_id', $suspendedUser->id)->count())->toBe(0);
});

test('suspended user cannot vote on forum questions or answers', function () {
    $author = User::factory()->create();
    $suspendedUser = User::factory()->create([
        'banned_until' => now()->addDays(2),
    ]);

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'slug' => 'test-slug-'.Str::random(6),
        'body' => 'Question body.',
    ]);

    $answer = $post->answers()->create([
        'user_id' => $author->id,
        'body' => 'Answer body.',
    ]);

    // Vote on post
    $this->actingAs($suspendedUser)
        ->post("/forum/posts/{$post->id}/vote", ['value' => 1])
        ->assertSessionHas('error');

    expect($post->fresh()->vote_score)->toBe(0);

    // Vote on answer
    $this->actingAs($suspendedUser)
        ->post("/forum/answers/{$answer->id}/vote", ['value' => 1])
        ->assertSessionHas('error');

    expect($answer->fresh()->vote_score)->toBe(0);
});
