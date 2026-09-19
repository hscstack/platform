<?php

use App\Models\AppSetting;
use App\Models\Blog;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Node;
use App\Models\NodeCompletion;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('public user profile can be rendered by username', function () {
    $user = User::factory()->create([
        'name' => 'Tajim Ahmed',
        'username' => 'tajim_pro',
        'institution' => 'Notre Dame College',
        'about' => 'HSC 26 Candidate',
    ]);

    $response = $this->get('/u/tajim_pro');

    $response->assertOk();
    $response->assertSee('<meta property="og:title" content="Tajim Ahmed (@tajim_pro)">', false);
    $response->assertSee('<meta property="og:type" content="profile">', false);
    $response->assertSee('<meta property="og:url" content="'.url('/u/tajim_pro').'">', false);
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->where('profileUser.username', 'tajim_pro')
        ->where('profileUser.name', 'Tajim Ahmed')
        ->where('profileUser.institution', 'Notre Dame College')
        ->has('suggestedUsers')
    );
});

test('viewing non-existent username renders 404 page', function () {
    $response = $this->get('/u/non_existent_student_999');

    $response->assertInertia(fn ($page) => $page->component('errors/404'));
});

test('public profile renders authored blogs when available', function () {
    $user = User::factory()->create([
        'username' => 'contributor_john',
        'is_verified' => true,
    ]);

    Blog::create([
        'user_id' => $user->id,
        'title' => 'Calculus Masterclass',
        'slug' => 'calculus-masterclass',
        'content' => 'Sample guide',
        'is_published' => true,
    ]);

    $response = $this->get('/u/contributor_john');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->where('profileUser.is_verified', true)
        ->has('blogs', 1)
        ->where('blogs.0.title', 'Calculus Masterclass')
    );
});

test('public profile accurately displays forum questions, answers, and activities', function () {
    $user = User::factory()->create(['username' => 'studious_student']);
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Vector',
        'slug' => 'vector',
    ]);

    $post = ForumPost::create([
        'user_id' => $user->id,
        'curriculum' => 'hsc',
        'subject_id' => $subject->id,
        'node_id' => $node->id,
        'title' => 'How to calculate cross product angle?',
        'body' => 'Need help with vector cross product.',
    ]);

    $answer = ForumAnswer::create([
        'forum_post_id' => $post->id,
        'user_id' => $user->id,
        'body' => 'Use A x B = |A||B|sin(theta) formula.',
    ]);

    $response = $this->get('/u/studious_student');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->has('forumPosts', 1)
        ->where('forumPosts.0.title', 'How to calculate cross product angle?')
        ->has('forumAnswers', 1)
        ->where('forumAnswers.0.body', 'Use A x B = |A||B|sin(theta) formula.')
        ->has('recentActivities.forum_posts', 1)
        ->has('recentActivities.forum_answers', 1)
    );
});

test('public profile excludes unapproved forum questions and answers', function () {
    $user = User::factory()->create(['username' => 'pending_user']);
    $subject = Subject::create([
        'name' => 'Chemistry',
        'slug' => 'chemistry',
        'course' => 'hsc',
        'tailwind_format' => 'bg-green-500',
        'icon' => 'beaker',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Organic Chemistry',
        'slug' => 'organic-chemistry',
    ]);

    $pendingPost = ForumPost::create([
        'user_id' => $user->id,
        'curriculum' => 'hsc',
        'subject_id' => $subject->id,
        'node_id' => $node->id,
        'title' => 'Pending Question Title',
        'body' => 'Pending post body.',
        'moderation_status' => 'pending',
    ]);

    ForumAnswer::create([
        'forum_post_id' => $pendingPost->id,
        'user_id' => $user->id,
        'body' => 'Answer to pending post',
    ]);

    $response = $this->get('/u/pending_user');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->has('forumPosts', 0)
        ->has('forumAnswers', 0)
        ->has('recentActivities.forum_posts', 0)
        ->has('recentActivities.forum_answers', 0)
    );
});

test('public profile includes both contributors and random users in suggestions', function () {
    $mainUser = User::factory()->create(['username' => 'current_user']);

    $contributor1 = User::factory()->create(['username' => 'contrib_1', 'is_verified' => true]);
    $contributor2 = User::factory()->create(['username' => 'contrib_2', 'is_verified' => true]);

    $student1 = User::factory()->create(['username' => 'student_1', 'is_verified' => false]);
    $student2 = User::factory()->create(['username' => 'student_2', 'is_verified' => false]);

    $response = $this->get('/u/current_user');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->has('suggestedUsers', 4)
    );
});

test('public profile displays created folders in recent activity', function () {
    $user = User::factory()->create(['username' => 'voter_student']);
    $subject = Subject::create([
        'name' => 'Higher Math',
        'slug' => 'higher-math',
        'course' => 'hsc',
        'tailwind_format' => 'bg-emerald-500',
        'icon' => 'calculator',
    ]);

    $parentFolder = Node::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'name' => 'Calculus',
        'slug' => 'calculus',
    ]);

    $response = $this->get('/u/voter_student');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->has('recentActivities.folders', 1)
        ->where('recentActivities.folders.0.title', 'Calculus')
    );
});

test('user model has is_verified boolean column and is single source of truth', function () {
    $regularUser = User::factory()->create(['is_verified' => false]);
    expect($regularUser->is_verified)->toBeFalse();

    $verifiedUser = User::factory()->create(['is_verified' => true]);
    expect($verifiedUser->is_verified)->toBeTrue();
    expect($verifiedUser->toArray())->toHaveKey('is_verified', true);
});

test('public profile returns created folders in recent activities', function () {
    $user = User::factory()->create(['username' => 'folder_creator']);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'icon' => 'atom',
    ]);

    Node::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'name' => 'Thermodynamics Chapter',
        'slug' => 'thermodynamics-chapter',
    ]);

    $response = $this->get('/u/folder_creator');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->has('recentActivities.folders', 1)
        ->where('recentActivities.folders.0.title', 'Thermodynamics Chapter')
        ->where('recentActivities.folders.0.subtitle', 'Physics')
        ->where('recentActivities.folders.0.url', '/physics/thermodynamics-chapter')
    );
});

test('profile update rejects inappropriate words in name, username, about, or institution', function () {
    AppSetting::set('global_chat_banned_words', 'badword, offensive');

    $user = User::factory()->create(['username' => 'clean_user']);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => 'Badword Person',
        'username' => 'clean_user',
    ]);
    $response->assertSessionHasErrors(['name']);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => 'John Doe',
        'username' => 'offensive_user',
    ]);
    $response->assertSessionHasErrors(['username']);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => 'John Doe',
        'about' => 'This is badword content',
    ]);
    $response->assertSessionHasErrors(['about']);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => 'John Doe',
        'institution' => 'Offensive College',
    ]);
    $response->assertSessionHasErrors(['institution']);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => 'John Doe',
        'about' => str_repeat('a', 256),
    ]);
    $response->assertSessionHasErrors(['about']);
});

test('user image_url accessor generates storage url for image_path', function () {
    $user1 = User::factory()->create(['image_path' => 'avatars/sample.jpg']);
    expect($user1->image_url)->toBe(Storage::url('avatars/sample.jpg'));

    $user2 = User::factory()->create(['image_path' => null]);
    expect($user2->image_url)->toBeNull();
});

test('public profile accurately displays syllabus progress and subject breakdown', function () {
    $user = User::factory()->create([
        'username' => 'syllabus_master',
        'curriculum' => 'hsc',
    ]);

    $subject1 = Subject::create([
        'name' => 'Physics 1st Paper',
        'slug' => 'physics-1st',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
        'is_trackable' => true,
    ]);

    $subject2 = Subject::create([
        'name' => 'Chemistry 1st Paper',
        'slug' => 'chemistry-1st',
        'course' => 'hsc',
        'tailwind_format' => 'bg-emerald-500',
        'icon' => 'flask',
        'is_trackable' => true,
    ]);

    $node1 = Node::create([
        'subject_id' => $subject1->id,
        'name' => 'Vector',
        'slug' => 'vector',
        'is_trackable' => true,
    ]);

    $node2 = Node::create([
        'subject_id' => $subject1->id,
        'name' => 'Dynamics',
        'slug' => 'dynamics',
        'is_trackable' => true,
    ]);

    $node3 = Node::create([
        'subject_id' => $subject2->id,
        'name' => 'Qualitative Chemistry',
        'slug' => 'qualitative-chem',
        'is_trackable' => true,
    ]);

    // Complete 1 out of 2 for Physics, 1 out of 1 for Chemistry => 2 / 3 total = 67%
    NodeCompletion::create(['user_id' => $user->id, 'node_id' => $node1->id]);
    NodeCompletion::create(['user_id' => $user->id, 'node_id' => $node3->id]);

    $response = $this->get('/u/syllabus_master');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->where('syllabusProgress.overallPercent', 67)
        ->where('syllabusProgress.completedChapters', 2)
        ->where('syllabusProgress.totalChapters', 3)
        ->has('syllabusProgress.subjects', 2)
        ->where('syllabusProgress.subjects.0.percent', 50)
        ->where('syllabusProgress.subjects.1.percent', 100)
    );
});
