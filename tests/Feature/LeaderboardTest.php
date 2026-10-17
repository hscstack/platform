<?php

use App\Models\DailyStudyLog;
use App\Models\Node;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('leaderboard this_week starts in Saturday morning (Asia/Dhaka)', function () {
    $this->withoutVite();

    // Freeze time to a Saturday at 10:00 AM in Asia/Dhaka
    // Saturday morning 2026-10-17 10:00:00 in Asia/Dhaka is 2026-10-17 04:00:00 UTC
    $satDhaka = Carbon::parse('2026-10-17 10:00:00', 'Asia/Dhaka');
    Carbon::setTestNow($satDhaka);

    $subject = Subject::create([
        'name' => 'Physics',
        'slug' => 'physics-lb',
        'course' => 'hsc',
        'tailwind_format' => 'bg-indigo-500',
        'icon' => 'atom',
    ]);
    $node = Node::create([
        'subject_id' => $subject->id,
        'name' => 'Chapter 1',
        'slug' => 'chapter-1-lb',
    ]);

    $studentOld = User::factory()->create(['name' => 'Old Week Student']);
    $studentNew = User::factory()->create(['name' => 'Saturday Student']);

    // Old log from Friday
    DailyStudyLog::create([
        'user_id' => $studentOld->id,
        'study_date' => '2026-10-16',
        'total_seconds' => 7200,
    ]);

    // New log on Saturday morning (Asia/Dhaka date: 2026-10-17)
    DailyStudyLog::create([
        'user_id' => $studentNew->id,
        'study_date' => '2026-10-17',
        'total_seconds' => 3600,
    ]);

    $contributorOld = User::factory()->create(['name' => 'Friday Contributor']);
    $contributorNew = User::factory()->create(['name' => 'Saturday Contributor']);

    // Resource created on Friday 23:30 Dhaka time (Friday 17:30 UTC)
    $resOld = new Resource([
        'node_id' => $node->id,
        'user_id' => $contributorOld->id,
        'title' => 'Friday Resource',
        'resource_type' => 'note',
        'content' => 'Old note',
    ]);
    $resOld->forceFill(['created_at' => Carbon::parse('2026-10-16 23:30:00', 'Asia/Dhaka')->setTimezone('UTC')])->save();

    // Resource created on Saturday 00:30 Dhaka time (Friday 18:30 UTC) - after Friday ends
    $resNew = new Resource([
        'node_id' => $node->id,
        'user_id' => $contributorNew->id,
        'title' => 'Saturday Morning Resource',
        'resource_type' => 'note',
        'content' => 'New note',
    ]);
    $resNew->forceFill(['created_at' => Carbon::parse('2026-10-17 00:30:00', 'Asia/Dhaka')->setTimezone('UTC')])->save();

    // Test students tab for this_week
    $responseStudents = $this->get('/leaderboard?tab=students&timeframe=this_week');
    $responseStudents->assertInertia(fn (Assert $page) => $page
        ->component('Leaderboard/Index')
        ->where('timeframe', 'this_week')
        ->where('leaderboard', fn ($lb) => collect($lb)->pluck('id')->contains($studentNew->id)
            && ! collect($lb)->pluck('id')->contains($studentOld->id)
        )
    );

    // Test contributors tab for this_week
    $responseContributors = $this->get('/leaderboard?tab=contributors&timeframe=this_week');
    $responseContributors->assertInertia(fn (Assert $page) => $page
        ->component('Leaderboard/Index')
        ->where('timeframe', 'this_week')
        ->where('leaderboard', fn ($lb) => collect($lb)->pluck('id')->contains($contributorNew->id)
            && ! collect($lb)->pluck('id')->contains($contributorOld->id)
        )
    );

    Carbon::setTestNow(); // Reset test clock
});

test('leaderboard transitions to new week precisely at Saturday 00:00:00 Asia/Dhaka', function () {
    $this->withoutVite();

    $tz = 'Asia/Dhaka';

    // 1 second before Friday ends: 2026-10-16 23:59:59
    Carbon::setTestNow(Carbon::parse('2026-10-16 23:59:59', $tz));
    $respFriday = $this->get('/leaderboard?tab=students&timeframe=this_week');
    $respFriday->assertOk();

    // Exactly when Friday ends and Saturday begins: 2026-10-17 00:00:00
    Carbon::setTestNow(Carbon::parse('2026-10-17 00:00:00', $tz));
    $respSaturday = $this->get('/leaderboard?tab=students&timeframe=this_week');
    $respSaturday->assertOk();

    Carbon::setTestNow();
});
