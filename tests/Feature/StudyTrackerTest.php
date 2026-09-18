<?php

use App\Models\DailyStudyLog;
use App\Models\Node;
use App\Models\NodeCompletion;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;

test('guests can view tracker page with subjects', function () {
    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1',
        'slug' => 'chapter-1',
    ]);

    $this->get('/tracker')
        ->assertOk();
});

test('authenticated user can view tracker page with stats and completions', function () {
    $user = User::factory()->create(['daily_target_minutes' => 180]);
    $subject = Subject::create([
        'name' => 'Chemistry',
        'slug' => 'chemistry',
        'course' => 'hsc',
        'tailwind_format' => 'bg-emerald-500',
        'icon' => 'flask',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Organic Chemistry',
        'slug' => 'organic-chem',
    ]);
    NodeCompletion::create([
        'user_id' => $user->id,
        'node_id' => $node->id,
    ]);
    DailyStudyLog::create([
        'user_id' => $user->id,
        'study_date' => Carbon::today()->format('Y-m-d'),
        'total_seconds' => 3600,
    ]);

    $this->actingAs($user)
        ->get('/tracker')
        ->assertOk();
});

test('guests cannot toggle node completion or log study time', function () {
    $subject = Subject::create([
        'name' => 'Math',
        'slug' => 'math',
        'course' => 'hsc',
        'tailwind_format' => 'bg-blue-500',
        'icon' => 'calculator',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Matrix',
        'slug' => 'matrix',
    ]);

    $this->post("/tracker/nodes/{$node->id}/toggle")
        ->assertRedirect('/login');

    $this->post('/tracker/log-time', ['seconds' => 1200])
        ->assertRedirect('/login');
});

test('authenticated user can toggle top-level node completion', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Biology',
        'slug' => 'biology',
        'course' => 'hsc',
        'tailwind_format' => 'bg-green-500',
        'icon' => 'dna',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Cell Structure',
        'slug' => 'cell-structure',
    ]);

    // Toggle on
    $this->actingAs($user)
        ->post("/tracker/nodes/{$node->id}/toggle")
        ->assertRedirect();

    $this->assertDatabaseHas('node_completions', [
        'user_id' => $user->id,
        'node_id' => $node->id,
    ]);

    // Toggle off
    $this->actingAs($user)
        ->post("/tracker/nodes/{$node->id}/toggle")
        ->assertRedirect();

    $this->assertDatabaseMissing('node_completions', [
        'user_id' => $user->id,
        'node_id' => $node->id,
    ]);
});

test('authenticated user can log study time and update daily target', function () {
    $user = User::factory()->create(['daily_target_minutes' => 120]);

    // Log 25 minutes
    $this->actingAs($user)
        ->post('/tracker/log-time', ['seconds' => 1500])
        ->assertRedirect();

    $this->assertDatabaseHas('daily_study_logs', [
        'user_id' => $user->id,
        'study_date' => Carbon::today()->format('Y-m-d'),
        'total_seconds' => 1500,
    ]);

    // Increment with another 15 minutes
    $this->actingAs($user)
        ->post('/tracker/log-time', ['seconds' => 900])
        ->assertRedirect();

    $this->assertDatabaseHas('daily_study_logs', [
        'user_id' => $user->id,
        'study_date' => Carbon::today()->format('Y-m-d'),
        'total_seconds' => 2400,
    ]);

    // Update target
    $this->actingAs($user)
        ->post('/tracker/target', ['daily_target_minutes' => 300])
        ->assertRedirect();

    expect($user->fresh()->daily_target_minutes)->toBe(300);
});

test('tracker only includes trackable subjects and trackable nodes flatly', function () {
    $trackableSubject = Subject::create([
        'name' => 'Trackable Subject',
        'slug' => 'trackable-subject',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
        'is_trackable' => true,
    ]);

    $untrackableSubject = Subject::create([
        'name' => 'Untrackable Subject',
        'slug' => 'untrackable-subject',
        'course' => 'hsc',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'atom',
        'is_trackable' => false,
    ]);

    $rootFolderUntrackable = Node::create([
        'subject_id' => $trackableSubject->id,
        'name' => 'Poem Folder',
        'slug' => 'poem-folder',
        'is_trackable' => false,
    ]);

    $childChapterTrackable = Node::create([
        'subject_id' => $trackableSubject->id,
        'parent_id' => $rootFolderUntrackable->id,
        'name' => 'Real Chapter 1',
        'slug' => 'real-chapter-1',
        'is_trackable' => true,
    ]);

    $response = $this->get('/tracker?course=hsc');
    $response->assertOk();

    $page = $response->original->getData()['page'];
    $subjects = $page['props']['subjects'];

    expect(count($subjects))->toBe(1);
    expect($subjects[0]['id'])->toBe($trackableSubject->id);
    expect(count($subjects[0]['nodes']))->toBe(1);
    expect($subjects[0]['nodes'][0]['id'])->toBe($childChapterTrackable->id);
    expect($subjects[0]['nodes'][0]['name'])->toBe('Real Chapter 1');
});

test('authenticated user can reset today study time', function () {
    $user = User::factory()->create();
    $todayStr = Carbon::today()->format('Y-m-d');

    DailyStudyLog::create([
        'user_id' => $user->id,
        'study_date' => $todayStr,
        'total_seconds' => 5000,
    ]);

    $this->actingAs($user)
        ->post('/tracker/reset-today')
        ->assertRedirect();

    $log = DailyStudyLog::where('user_id', $user->id)->where('study_date', $todayStr)->first();
    expect($log->total_seconds)->toBe(0);
});

test('switching curriculum updates user profile and clears full chapter track', function () {
    $user = User::factory()->create(['curriculum' => 'hsc']);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
        'is_trackable' => true,
    ]);

    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Vector',
        'slug' => 'vector',
        'is_trackable' => true,
    ]);

    NodeCompletion::create([
        'user_id' => $user->id,
        'node_id' => $node->id,
    ]);

    expect(NodeCompletion::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->post('/tracker/curriculum', ['curriculum' => 'ssc'])
        ->assertRedirect('/tracker');

    expect($user->fresh()->curriculum)->toBe('ssc');
    expect(NodeCompletion::where('user_id', $user->id)->count())->toBe(0);
});

test('tracker renders user curriculum for authenticated user', function () {
    $user = User::factory()->create(['curriculum' => 'ssc']);

    $response = $this->actingAs($user)->get('/tracker');
    $response->assertOk();

    $page = $response->original->getData()['page'];
    expect($page['props']['course'])->toBe('ssc');
});
