<?php

use App\Models\AppSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can view the notice edit page', function () {
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $response = $this->actingAs($admin)->get('/admin/notice');

    $response->assertStatus(200);
});

test('admin can update notice without image', function () {
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $response = $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Test Notice Title',
        'message' => 'Test notice message content',
        'show_button' => true,
        'button_title' => 'Click Here',
        'button_link' => 'https://example.com',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.notice.edit'));
    $response->assertSessionHas('success', 'Notice updated.');

    $raw = AppSetting::get('site_notice');
    $notice = is_array($raw) ? $raw : json_decode($raw, true);

    expect($notice['title'])->toBe('Test Notice Title')
        ->and($notice['message'])->toBe('Test notice message content')
        ->and($notice['show_button'])->toBeTrue()
        ->and($notice['button_title'])->toBe('Click Here')
        ->and($notice['button_link'])->toBe('https://example.com')
        ->and($notice['is_active'])->toBeTrue();
});

test('admin can upload a notice image and store only the path', function () {
    Storage::fake();
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $file = UploadedFile::fake()->image('notice.png', 800, 600);

    $response = $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Notice with Image',
        'message' => 'Here is a notice with image',
        'image' => $file,
        'show_button' => false,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.notice.edit'));

    $raw = AppSetting::get('site_notice');
    $notice = is_array($raw) ? $raw : json_decode($raw, true);

    expect($notice['image'])->not->toBeNull()
        ->and(str_starts_with($notice['image'], 'notices/'))->toBeTrue();

    Storage::assertExists($notice['image']);
});

test('admin can delete an existing notice image', function () {
    Storage::fake();
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $file = UploadedFile::fake()->image('notice.png', 800, 600);
    $path = $file->store('notices');

    AppSetting::set('site_notice', json_encode([
        'title' => 'Notice 1',
        'message' => 'Notice 1 message',
        'image' => $path,
        'is_active' => true,
    ]));

    Storage::assertExists($path);

    $response = $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Notice 2',
        'message' => 'Notice 2 message',
        'remove_image' => true,
        'show_button' => false,
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    $raw = AppSetting::get('site_notice');
    $notice = is_array($raw) ? $raw : json_decode($raw, true);

    expect($notice['image'])->toBeNull();
    Storage::assertMissing($path);
});
