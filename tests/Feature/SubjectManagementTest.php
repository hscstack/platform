<?php

use App\Models\Subject;

test('admin subject lifecycle can create, edit, and delete a subject', function () {
    $admin = adminUserWithPermissions([
        'view admin',
        'create subjects',
        'edit subjects',
        'delete subjects',
    ]);

    $createResponse = $this->actingAs($admin)->post('/admin/subjects', [
        'name' => 'Platform Testing',
        'tailwind_format' => 'bg-slate-500',
        'icon' => 'book-open',
        'sort_order' => 1,
    ]);

    $createResponse->assertRedirect(route('admin.subjects.index'));
    $this->assertDatabaseHas('subjects', ['name' => 'Platform Testing']);

    $subject = Subject::where('name', 'Platform Testing')->firstOrFail();

    $updateResponse = $this->actingAs($admin)->patch("/admin/subjects/edit/{$subject->id}", [
        'name' => 'Platform Testing Updated',
        'tailwind_format' => 'bg-slate-600',
        'icon' => 'book-open',
        'sort_order' => 2,
    ]);

    $updateResponse->assertRedirect(route('admin.subjects.index'));
    $this->assertDatabaseHas('subjects', ['name' => 'Platform Testing Updated', 'sort_order' => 2]);

    $deleteResponse = $this->actingAs($admin)->delete("/admin/subjects/{$subject->id}");

    $deleteResponse->assertRedirect();
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
