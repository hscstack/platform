<?php

use App\Models\Subject;

test('admin can create a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'create subjects']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Platform Testing',
        'english_name' => 'Testing Platform Subject',
        'course' => 'hsc',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('subjects', [
        'name' => 'Platform Testing',
        'english_name' => 'Testing Platform Subject',
        'course' => 'hsc',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);
});

test('admin can update a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'edit subjects']);

    $subject = Subject::create([
        'name' => 'Platform Testing',
        'english_name' => 'Testing Platform Subject',
        'slug' => 'platform-testing',
        'course' => 'hsc',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->patch("/admin/subjects/edit/{$subject->id}", [
        'name' => 'Platform Testing Updated',
        'english_name' => 'Testing Platform Updated',
        'course' => 'ssc',
        'tailwind_format' => 'bg-slate-600',
        'icon' => 'book-open',
        'sort_order' => 2,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'name' => 'Platform Testing Updated',
        'english_name' => 'Testing Platform Updated',
        'course' => 'ssc',
        'sort_order' => 2,
    ]);
});

test('admin can delete a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'delete subjects']);

    $subject = Subject::create([
        'name' => 'Platform Testing',
        'slug' => 'platform-testing',
        'course' => 'hsc',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/subjects/{$subject->id}");

    $response->assertRedirect();

    $this->assertDatabaseMissing('subjects', [
        'id' => $subject->id,
    ]);
});

test('admin can create a subject with custom slug', function () {
    $admin = adminUserWithPermissions(['view admin', 'create subjects']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Custom Slug Subject',
        'english_name' => 'Custom Slug Eng',
        'slug' => 'my-special-custom-slug',
        'course' => 'hsc',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('subjects', [
        'name' => 'Custom Slug Subject',
        'slug' => 'my-special-custom-slug',
    ]);
});

test('admin can update a subject with custom slug', function () {
    $admin = adminUserWithPermissions(['view admin', 'edit subjects']);

    $subject = Subject::create([
        'name' => 'Existing Subject',
        'slug' => 'existing-subject-slug',
        'course' => 'hsc',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->patch("/admin/subjects/edit/{$subject->id}", [
        'slug' => 'updated-custom-slug',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'slug' => 'updated-custom-slug',
    ]);
});

test('invalid subject creation is rejected by validation', function () {
    $admin = adminUserWithPermissions(['view admin', 'create subjects']);

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
