<?php

use App\Models\Chapter;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;

test('notes index page loads successfully with inertia component', function () {
    $this->get('/notes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notes/Index')
            ->has('resources.data')
            ->has('subjects')
            ->has('filters')
        );
});

test('notes index filters by subject and course', function () {
    $physics = Subject::create([
        'name' => 'Physics 1st Paper',
        'course' => 'hsc',
        'group' => 'science',
    ]);

    $math = Subject::create([
        'name' => 'General Math',
        'course' => 'ssc',
        'group' => 'science',
    ]);

    $author = User::factory()->create();

    Resource::create([
        'subject_id' => $physics->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'HSC Physics Vector Note',
        'items' => [['format' => 'image', 'source' => 'test.jpg']],
        'status' => 'approved',
        'vote_score' => 10,
    ]);

    Resource::create([
        'subject_id' => $math->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'SSC Math Geometry Note',
        'items' => [['format' => 'image', 'source' => 'test2.jpg']],
        'status' => 'approved',
        'vote_score' => 5,
    ]);

    // Test HSC filter (default)
    $this->get('/notes?curriculum=hsc&group=science')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notes/Index')
            ->where('resources.data.0.title', 'HSC Physics Vector Note')
            ->has('resources.data', 1)
        );

    // Test SSC filter
    $this->get('/notes?curriculum=ssc&group=science')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notes/Index')
            ->where('resources.data.0.title', 'SSC Math Geometry Note')
            ->has('resources.data', 1)
        );
});

test('notes index filters subject-wide resources when node_id is whole', function () {
    $subject = Subject::create([
        'name' => 'Chemistry',
        'course' => 'hsc',
        'group' => 'science',
    ]);

    $chapter = Chapter::create([
        'subject_id' => $subject->id,
        'name' => 'Atomic Structure',
    ]);

    $author = User::factory()->create();

    // 1. Whole-book / subject-wide resource (node_id = null)
    Resource::create([
        'subject_id' => $subject->id,
        'chapter_id' => null,
        'user_id' => $author->id,
        'resource_type' => 'other',
        'title' => 'Full Chemistry Textbook PDF',
        'items' => [['format' => 'pdf', 'source' => 'test.pdf']],
        'status' => 'approved',
        'vote_score' => 20,
    ]);

    // 2. Chapter-specific resource (node_id = chapter->id)
    Resource::create([
        'subject_id' => $subject->id,
        'chapter_id' => $chapter->id,
        'user_id' => $author->id,
        'resource_type' => 'note',
        'title' => 'Atomic Structure Handnote',
        'items' => [['format' => 'image', 'source' => 'chapter.jpg']],
        'status' => 'approved',
        'vote_score' => 15,
    ]);

    // Query whole-book / subject-wide resources
    $this->get("/notes?curriculum=hsc&group=science&subject_id={$subject->id}&node_id=whole")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notes/Index')
            ->has('resources.data', 1)
            ->where('resources.data.0.title', 'Full Chemistry Textbook PDF')
        );

    // Query chapter-specific resources
    $this->get("/notes?curriculum=hsc&group=science&subject_id={$subject->id}&node_id={$chapter->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notes/Index')
            ->has('resources.data', 1)
            ->where('resources.data.0.title', 'Atomic Structure Handnote')
        );
});
