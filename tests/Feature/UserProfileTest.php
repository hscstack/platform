<?php

use App\Models\Blog;
use App\Models\Node;
use App\Models\ResourceCompletion;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Role;

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
    Role::create(['name' => 'editor']);

    $user = User::factory()->create(['username' => 'contributor_john']);
    $user->assignRole('editor');

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
        ->where('profileUser.is_staff', true)
        ->where('stats.blogsCount', 1)
        ->has('blogs', 1)
        ->where('blogs.0.title', 'Calculus Masterclass')
    );
});

test('public profile accurately displays completed study topics and activities', function () {
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
    $resource = App\Models\Resource::create([
        'node_id' => $node->id,
        'title' => 'Vector Lecture Notes',
        'resource_type' => 'image',
        'file_path' => 'resources/vector.jpg',
    ]);

    ResourceCompletion::create([
        'user_id' => $user->id,
        'resource_id' => $resource->id,
    ]);

    $response = $this->get('/u/studious_student');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->where('stats.completedResourcesCount', 1)
        ->has('recentCompletions', 1)
        ->where('recentCompletions.0.title', 'Vector Lecture Notes')
        ->has('recentActivities.completions', 1)
    );
});

test('public profile includes both contributors and random users in suggestions', function () {
    $role = Role::firstOrCreate(['name' => 'editor']);

    $mainUser = User::factory()->create(['username' => 'current_user']);

    $contributor1 = User::factory()->create(['username' => 'contrib_1']);
    $contributor1->assignRole($role);

    $contributor2 = User::factory()->create(['username' => 'contrib_2']);
    $contributor2->assignRole($role);

    $student1 = User::factory()->create(['username' => 'student_1']);
    $student2 = User::factory()->create(['username' => 'student_2']);

    $response = $this->get('/u/current_user');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->has('suggestedUsers', 4)
    );
});

test('public profile displays latest upvoted folders in recent activity', function () {
    $user = User::factory()->create(['username' => 'voter_student']);
    $subject = Subject::create([
        'name' => 'Higher Math',
        'slug' => 'higher-math',
        'course' => 'hsc',
        'tailwind_format' => 'bg-emerald-500',
        'icon' => 'calculator',
    ]);

    $parentFolder = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Calculus',
        'slug' => 'calculus',
    ]);

    $childFolder = Node::create([
        'subject_id' => $subject->id,
        'parent_id' => $parentFolder->id,
        'name' => 'Differentiation Master Notes',
        'slug' => 'diff-notes',
    ]);

    \App\Models\NodeVote::create([
        'node_id' => $childFolder->id,
        'user_id' => $user->id,
        'type' => 'up',
    ]);

    $response = $this->get('/u/voter_student');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('User/Show')
        ->has('recentActivities.upvotes', 1)
        ->where('recentActivities.upvotes.0.title', 'Differentiation Master Notes')
        ->where('recentActivities.upvotes.0.subtitle', 'Higher Math · Calculus')
        ->where('recentActivities.upvotes.0.url', '/higher-math/calculus/diff-notes')
    );
});
