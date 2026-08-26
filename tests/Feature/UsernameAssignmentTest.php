<?php

use App\Models\User;

test('new user automatically gets default student_{id} username on creation', function () {
    $user = User::create([
        'name' => 'Tajim Ahmed',
        'email' => 'tajim@example.com',
    ]);

    expect($user->username)->toBe("student_{$user->id}");
    expect($user->username)->toMatch('/^student_\d+$/');
});

test('explicitly provided username is preserved on creation', function () {
    $user = User::create([
        'name' => 'Custom Name',
        'username' => 'custom_handle_99',
        'email' => 'custom@example.com',
    ]);

    expect($user->username)->toBe('custom_handle_99');
});
