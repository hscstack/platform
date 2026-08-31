<?php

use App\Models\AppSetting;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Node;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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
        'title' => 'How to solve vector addition problems?',
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

test('authenticated user can view the ask question page and guest is redirected', function () {
    $guestResponse = $this->get('/forum/ask');
    $guestResponse->assertRedirect('/login');

    $user = User::factory()->create();
    $authResponse = $this->actingAs($user)->get('/forum/ask');
    $authResponse->assertOk()
        ->assertInertia(fn ($page) => $page->component('Forum/Create'));
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
    $node = Node::create([
        'subject_id' => $subject->id,
        'user_id' => $user->id,
        'name' => 'Integration',
        'slug' => 'integration',
    ]);

    $response = $this->actingAs($user)->post('/forum', [
        'title' => 'Need help with Calculus Integration',
        'body' => 'How do I evaluate trigonometric substitution integrals?',
        'curriculum' => 'hsc',
        'subject_id' => $subject->id,
        'node_id' => $node->id,
    ]);

    $post = ForumPost::first();
    expect($post)->not->toBeNull()
        ->and($post->title)->toBe('Need help with Calculus Integration')
        ->and($post->slug)->toBe('need-help-with-calculus-integration')
        ->and($post->subject_id)->toBe($subject->id)
        ->and($post->node_id)->toBe($node->id)
        ->and($post->user_id)->toBe($user->id);

    $response->assertRedirect("/forum/questions/{$post->slug}");
});

test('authenticated user can create a general question with null subject and chapter', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/forum', [
        'title' => 'Which engineering college has the best campus life?',
        'body' => 'I am confused between multiple engineering universities and need student feedback.',
        'curriculum' => 'hsc',
        'subject_id' => null,
        'node_id' => null,
    ]);

    $post = ForumPost::latest('id')->first();
    expect($post)->not->toBeNull()
        ->and($post->title)->toBe('Which engineering college has the best campus life?')
        ->and($post->subject_id)->toBeNull()
        ->and($post->node_id)->toBeNull();

    $response->assertRedirect("/forum/questions/{$post->slug}");
});

test('user can post answer and reply with 1-level nesting enforced', function () {
    $author = User::factory()->create();
    $replier = User::factory()->create();

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'title' => 'Sample Chemistry Question',
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
        'title' => 'Vote Testing Question Title',
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

test('author can toggle answered status', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();

    $post = ForumPost::create([
        'user_id' => $author->id,
        'curriculum' => 'hsc',
        'title' => 'Question to be answered',
        'body' => 'Sample body content for toggle test.',
    ]);

    // Other user cannot toggle
    $this->actingAs($other)->post("/forum/posts/{$post->id}/toggle-answered")
        ->assertForbidden();

    // Author can toggle
    $this->actingAs($author)->post("/forum/posts/{$post->id}/toggle-answered");
    $post->refresh();
    expect($post->is_answered)->toBeTrue();

    $this->actingAs($author)->post("/forum/posts/{$post->id}/toggle-answered");
    $post->refresh();
    expect($post->is_answered)->toBeFalse();
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
        'title' => 'Sample Title with fuck word',
        'body' => 'Clean body text for testing.',
        'curriculum' => 'hsc',
        'subject_id' => $subject->id,
    ]);
    $response->assertSessionHasErrors('title');

    $post = ForumPost::create([
        'user_id' => $user->id,
        'curriculum' => 'hsc',
        'title' => 'Clean Question Title',
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
        'title' => 'Post to be deleted',
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
        'title' => 'Post for Answer Vote Test',
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
    $sscNode = Node::create([
        'subject_id' => $sscSubject->id,
        'name' => 'Algebra Chapter',
        'slug' => 'algebra-chapter',
        'sort_order' => 1,
    ]);

    // Post with HSC curriculum but SSC subject -> subject_id and node_id should be nullified
    $this->actingAs($user)->post('/forum', [
        'curriculum' => 'hsc',
        'subject_id' => $sscSubject->id,
        'node_id' => $sscNode->id,
        'title' => 'Taxonomy Mismatch Question',
        'body' => 'Checking taxonomy boundary conditions.',
    ]);

    $post = ForumPost::where('title', 'Taxonomy Mismatch Question')->first();
    expect($post)->not->toBeNull()
        ->and($post->subject_id)->toBeNull()
        ->and($post->node_id)->toBeNull();

    // Post with HSC subject but mismatched SSC node -> node_id should be nullified
    $this->actingAs($user)->post('/forum', [
        'curriculum' => 'hsc',
        'subject_id' => $hscSubject->id,
        'node_id' => $sscNode->id,
        'title' => 'Mismatched Node Question',
        'body' => 'Checking node subject boundary conditions.',
    ]);

    $post2 = ForumPost::where('title', 'Mismatched Node Question')->first();
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
        'title' => 'Post with Image Answers',
        'body' => 'Testing image cleanup.',
        'image_path' => 'forum/posts/test_post.png',
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
