<?php

use App\Models\Node;
use App\Models\Subject;

test('admin can create a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Platform Testing',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Subject created successfully.');

    $this->assertDatabaseHas('subjects', [
        'name' => 'Platform Testing',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);
});

test('admin can update a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    $subject = Subject::create([
        'name' => 'Platform Testing',
        'slug' => 'platform-testing',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->patch("/admin/subjects/edit/{$subject->id}", [
        'name' => 'Platform Testing Updated',
        'course' => 'ssc',
        'group' => 'commerce',
        'tailwind_format' => 'bg-slate-600',
        'icon' => 'book-open',
        'sort_order' => 2,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Subject updated successfully.');

    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'name' => 'Platform Testing Updated',
        'course' => 'ssc',
        'group' => 'commerce',
        'sort_order' => 2,
    ]);
});

test('admin can delete a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    $subject = Subject::create([
        'name' => 'Platform Testing',
        'slug' => 'platform-testing',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/subjects/{$subject->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Subject deleted successfully.');

    $this->assertDatabaseMissing('subjects', [
        'id' => $subject->id,
    ]);
});

test('admin can create a subject with custom slug', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Custom Slug Subject',
        'slug' => 'my-special-custom-slug',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Subject created successfully.');

    $this->assertDatabaseHas('subjects', [
        'name' => 'Custom Slug Subject',
        'slug' => 'my-special-custom-slug',
        'group' => 'science',
    ]);
});

test('admin can update a subject with custom slug', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    $subject = Subject::create([
        'name' => 'Existing Subject',
        'slug' => 'existing-subject-slug',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->patch("/admin/subjects/edit/{$subject->id}", [
        'slug' => 'updated-custom-slug',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Subject updated successfully.');

    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'slug' => 'updated-custom-slug',
    ]);
});

test('invalid subject creation is rejected by validation', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => '',
        'course' => '',
        'tailwind_format' => '',
        'icon' => '',
        'sort_order' => '',
    ]);

    $response->assertStatus(302);

    $response->assertSessionHasErrors([
        'name',
        'slug',
        'course',
        'tailwind_format',
        'icon',
        'sort_order',
    ]);
});

test('admin can create subjects with same name in different courses', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    Subject::create([
        'name' => 'Physics',
        'slug' => 'hsc-physics',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Physics',
        'course' => 'ssc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Subject created successfully.');

    $this->assertDatabaseHas('subjects', [
        'name' => 'Physics',
        'course' => 'ssc',
        'slug' => 'ssc-physics',
        'group' => 'science',
    ]);
});

test('admin can toggle disable on a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Disabled Subject',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
        'is_disabled' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subjects', [
        'name' => 'Disabled Subject',
        'is_disabled' => 1,
    ]);

    $subject = Subject::where('name', 'Disabled Subject')->first();

    $response = $this->actingAs($admin)->patch("/admin/subjects/edit/{$subject->id}", [
        'is_disabled' => false,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'is_disabled' => 0,
    ]);
});

test('disabled subjects are hidden from home page', function () {
    $this->withoutVite();

    $enabledSubject = Subject::create([
        'name' => 'Enabled Subject',
        'slug' => 'enabled-subject',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
        'is_disabled' => false,
    ]);

    $disabledSubject = Subject::create([
        'name' => 'Disabled Subject',
        'slug' => 'disabled-subject',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 2,
        'is_disabled' => true,
    ]);

    $response = $this->get('/?group=science');
    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Feed')
        ->where('subjects', fn ($subjects) => collect($subjects)->pluck('id')->contains($enabledSubject->id)
            && ! collect($subjects)->pluck('id')->contains($disabledSubject->id)
        )
    );
});

test('disabled subjects return 404 for everyone including admins', function () {
    $this->withoutVite();

    $disabledSubject = Subject::create([
        'name' => 'Hidden Subject',
        'slug' => 'hidden-subject',
        'course' => 'hsc',
        'group' => 'science',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
        'is_disabled' => true,
    ]);

    $chapter = Node::create([
        'subject_id' => $disabledSubject->id,
        'name' => 'Chapter 1',
        'slug' => 'chapter-1',
    ]);

    // Guest / Regular user
    $this->get('/hidden-subject')
        ->assertInertia(fn ($page) => $page->component('errors/404'));

    $this->get('/hidden-subject/chapter-1')
        ->assertInertia(fn ($page) => $page->component('errors/404'));

    // Admin user with permissions also gets 404
    $admin = adminUserWithPermissions(['manage curriculum']);
    $this->actingAs($admin)
        ->get('/hidden-subject')
        ->assertInertia(fn ($page) => $page->component('errors/404'));

    $this->actingAs($admin)
        ->get('/hidden-subject/chapter-1')
        ->assertInertia(fn ($page) => $page->component('errors/404'));
});

test('admin can define and edit subject type group and optional status', function () {
    $admin = adminUserWithPermissions(['view admin', 'manage curriculum']);

    // Create a common subject
    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Bangla 1st Paper',
        'course' => 'hsc',
        'group' => 'common',
        'tailwind_format' => 'bg-emerald-50 text-emerald-600',
        'icon' => 'BookOpen',
        'sort_order' => 1,
        'is_optional' => false,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subjects', [
        'name' => 'Bangla 1st Paper',
        'group' => 'common',
        'is_optional' => false,
    ]);

    $subject = Subject::where('name', 'Bangla 1st Paper')->first();

    // Edit to humanities (arts) and mark as optional
    $response = $this->actingAs($admin)->patch("/admin/subjects/edit/{$subject->id}", [
        'group' => 'humanities',
        'is_optional' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'group' => 'humanities',
        'is_optional' => true,
    ]);
});
