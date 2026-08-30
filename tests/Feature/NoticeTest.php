<?php

use App\Models\Notice;
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

    $notice = Notice::singleton();
    expect($notice->title)->toBe('Test Notice Title')
        ->and($notice->message)->toBe('Test notice message content')
        ->and($notice->show_button)->toBeTrue()
        ->and($notice->button_title)->toBe('Click Here')
        ->and($notice->button_link)->toBe('https://example.com')
        ->and($notice->is_active)->toBeTrue();
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

    $notice = Notice::singleton();
    $rawPath = $notice->getRawOriginal('image');

    expect($rawPath)->not->toBeNull()
        ->and($rawPath)->toStartWith('notices/')
        ->and($notice->image)->toBe(Storage::url($rawPath));

    Storage::assertExists($rawPath);
});

test('admin can replace and remove notice image', function () {
    Storage::fake();
    $admin = adminUserWithPermissions(['view admin', 'edit notice']);

    $file1 = UploadedFile::fake()->image('first.png');

    $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Notice 1',
        'message' => 'Notice 1 message',
        'image' => $file1,
        'show_button' => false,
        'is_active' => true,
    ]);

    $notice = Notice::singleton();
    $firstPath = $notice->getRawOriginal('image');
    Storage::assertExists($firstPath);

    // Replace with second image
    $file2 = UploadedFile::fake()->image('second.png');

    $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Notice 2',
        'message' => 'Notice 2 message',
        'image' => $file2,
        'show_button' => false,
        'is_active' => true,
    ]);

    $notice->refresh();
    $secondPath = $notice->getRawOriginal('image');

    Storage::assertMissing($firstPath);
    Storage::assertExists($secondPath);

    // Remove image
    $this->actingAs($admin)->post('/admin/notice', [
        'title' => 'Notice 3',
        'message' => 'Notice 3 message',
        'remove_image' => true,
        'show_button' => false,
        'is_active' => true,
    ]);

    $notice->refresh();
    expect($notice->getRawOriginal('image'))->toBeNull()
        ->and($notice->image)->toBeNull();

    Storage::assertMissing($secondPath);
});
