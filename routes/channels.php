<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('global-chat', function (User $user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'image_url' => $user->image_url,
    ];
});

Broadcast::channel('{env}.global-chat', function (User $user, string $env) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'image_url' => $user->image_url,
    ];
});
