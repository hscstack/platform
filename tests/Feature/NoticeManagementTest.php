<?php

use App\Models\Notice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can view notice edit page', function () {
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $response = $this->actingAs($admin)->get('/admin/notice');

    $response->assertOk();
});

test('admin can update notice text fields', function () {
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $response = $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Updated Notice Title',
        'message' => 'Important announcement message.',
        'show_button' => true,
        'button_title' => 'Check Details',
        'button_link' => 'https://hscstack.site/blogs',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.notice.edit'));

    $this->assertDatabaseHas('notices', [
        'title' => 'Updated Notice Title',
        'message' => 'Important announcement message.',
        'show_button' => 1,
        'button_title' => 'Check Details',
        'button_link' => 'https://hscstack.site/blogs',
        'is_active' => 1,
    ]);
});

test('admin can upload a notice image', function () {
    Storage::fake();
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $file = UploadedFile::fake()->image('notice.jpg', 800, 400);

    $response = $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Notice with Image',
        'message' => 'Check the image above.',
        'image' => $file,
        'show_button' => false,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.notice.edit'));

    $notice = Notice::singleton();
    expect($notice->getRawOriginal('image'))->not->toBeNull();
    Storage::assertExists($notice->getRawOriginal('image'));
});

test('admin can remove notice image', function () {
    Storage::fake();
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $notice = Notice::singleton();
    $notice->update(['image' => 'notices/sample.jpg']);

    $response = $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Notice without Image',
        'message' => 'No image anymore.',
        'remove_image' => true,
        'show_button' => false,
        'is_active' => false,
    ]);

    $response->assertRedirect(route('admin.notice.edit'));

    $notice->refresh();
    expect($notice->getRawOriginal('image'))->toBeNull();
});
