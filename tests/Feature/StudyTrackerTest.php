<?php

use App\Models\Chapter;
use App\Models\ChapterCompletion;
use App\Models\DailyStudyLog;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;

test('guests can view tracker page with subjects', function () {
    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
    ]);
    Chapter::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1',
    ]);

    $this->get('/tracker')
        ->assertOk();
});

test('authenticated user can view tracker page with stats and completions', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Chemistry',
        'course' => 'hsc',
    ]);
    $node = Chapter::create([
        'subject_id' => $subject->id,
        'name' => 'Organic Chemistry',
    ]);
    ChapterCompletion::create([
        'user_id' => $user->id,
        'chapter_id' => $node->id,
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
        'course' => 'hsc',
    ]);
    $node = Chapter::create([
        'subject_id' => $subject->id,
        'name' => 'Calculus',
    ]);

    $this->post("/tracker/chapters/{$node->id}/toggle")
        ->assertRedirect();

    $this->post('/tracker/log-time', ['seconds' => 1200])
        ->assertRedirect();

    expect(ChapterCompletion::count())->toBe(0);
    expect(DailyStudyLog::count())->toBe(0);
});

test('authenticated user can toggle node completion multiple times (idempotent toggling)', function () {
    $user = User::factory()->create();
    $subject = Subject::create([
        'name' => 'Biology',
        'course' => 'hsc',
    ]);
    $node = Chapter::create([
        'subject_id' => $subject->id,
        'name' => 'Cell Structure',
    ]);

    // First toggle: complete
    $this->actingAs($user)
        ->post("/tracker/chapters/{$node->id}/toggle")
        ->assertRedirect();

    $this->assertDatabaseHas('chapter_completions', [
        'user_id' => $user->id,
        'chapter_id' => $node->id,
    ]);

    // Second toggle: uncomplete
    $this->actingAs($user)
        ->post("/tracker/chapters/{$node->id}/toggle")
        ->assertRedirect();

    $this->assertDatabaseMissing('chapter_completions', [
        'user_id' => $user->id,
        'chapter_id' => $node->id,
    ]);
});

test('authenticated user can log study time cumulatively for today', function () {
    $user = User::factory()->create();

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
});

test('logging study time cannot exceed daily max cap of 16 hours', function () {
    $user = User::factory()->create();

    // Rejects request exceeding max daily limit in single call
    $this->actingAs($user)
        ->post('/tracker/log-time', ['seconds' => DailyStudyLog::MAX_DAILY_SECONDS + 1])
        ->assertSessionHasErrors('seconds');

    // Allows logging up to max daily limit
    $this->actingAs($user)
        ->post('/tracker/log-time', ['seconds' => DailyStudyLog::MAX_DAILY_SECONDS])
        ->assertRedirect();

    $this->assertDatabaseHas('daily_study_logs', [
        'user_id' => $user->id,
        'study_date' => Carbon::today()->format('Y-m-d'),
        'total_seconds' => DailyStudyLog::MAX_DAILY_SECONDS,
    ]);

    // Subsequent logs within the same day are capped at max
    $this->actingAs($user)
        ->post('/tracker/log-time', ['seconds' => 600])
        ->assertRedirect();

    $log = DailyStudyLog::where('user_id', $user->id)
        ->where('study_date', Carbon::today()->format('Y-m-d'))
        ->first();

    expect($log->total_seconds)->toBe(DailyStudyLog::MAX_DAILY_SECONDS);
});

test('tracker includes curriculum subjects and chapters flatly', function () {
    $trackableSubject = Subject::create([
        'name' => 'Trackable Subject',
        'course' => 'hsc',
    ]);

    $chapterTrackable = Chapter::create([
        'subject_id' => $trackableSubject->id,
        'name' => 'Real Chapter 1',
    ]);

    $response = $this->get('/tracker?course=hsc');
    $response->assertOk();

    $page = $response->original->getData()['page'];
    $subjects = $page['props']['subjects'];

    expect(count($subjects))->toBeGreaterThanOrEqual(1);
    expect($subjects[0]['id'])->toBe($trackableSubject->id);
    expect(count($subjects[0]['nodes']))->toBe(1);
    expect($subjects[0]['nodes'][0]['id'])->toBe($chapterTrackable->id);
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
    expect($log)->toBeNull();
});

test('switching curriculum updates user profile and preserves completed node track', function () {
    $user = User::factory()->create(['curriculum' => 'hsc', 'group' => 'science']);

    $subject = Subject::create([
        'name' => 'Physics',
        'course' => 'hsc',
        'group' => 'science',
    ]);

    $node = Chapter::create([
        'subject_id' => $subject->id,
        'name' => 'Vector',
    ]);

    ChapterCompletion::create([
        'user_id' => $user->id,
        'chapter_id' => $node->id,
    ]);

    expect(ChapterCompletion::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->from('/tracker')
        ->post('/tracker/curriculum', [
            'curriculum' => 'ssc',
            'group' => 'commerce',
        ])
        ->assertRedirect('/tracker');

    expect($user->fresh()->curriculum)->toBe('ssc');
    expect($user->fresh()->group)->toBe('commerce');
    expect(ChapterCompletion::where('user_id', $user->id)->count())->toBe(1);
});

test('tracker renders user curriculum for authenticated user', function () {
    $user = User::factory()->create(['curriculum' => 'ssc']);

    $response = $this->actingAs($user)->get('/tracker');
    $response->assertOk();

    $page = $response->original->getData()['page'];
    expect($page['props']['course'])->toBe('ssc');
});
