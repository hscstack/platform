<?php

use App\Models\Subject;

test('admin can create a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'create subjects']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Platform Testing',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response->assertRedirect(route('admin.subjects.index'));
    $this->assertDatabaseHas('subjects', [
        'name' => 'Platform Testing',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);
});

test('admin can update a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'edit subjects']);

    $subject = Subject::create([
        'name' => 'Platform Testing',
        'slug' => 'platform-testing',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->patch("/admin/subjects/edit/{$subject->id}", [
        'name' => 'Platform Testing Updated',
        'tailwind_format' => 'bg-slate-600',
        'icon' => 'book-open',
        'sort_order' => 2,
    ]);

    $response->assertRedirect(route('admin.subjects.index'));
    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'name' => 'Platform Testing Updated',
        'sort_order' => 2,
    ]);
});

test('admin can delete a subject', function () {
    $admin = adminUserWithPermissions(['view admin', 'delete subjects']);

    $subject = Subject::create([
        'name' => 'Platform Testing',
        'slug' => 'platform-testing',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/subjects/{$subject->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
});

test('invalid subject creation is rejected by validation', function () {
    $admin = adminUserWithPermissions(['view admin', 'create subjects']);

    $response = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => '',
        'tailwind_format' => '',
        'icon' => '',
        'sort_order' => '',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['name', 'slug', 'tailwind_format', 'icon', 'sort_order']);
});
