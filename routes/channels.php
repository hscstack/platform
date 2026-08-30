<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Presence channel for tracking active users on Global Chat.
Broadcast::channel('global-chat', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('{env}.global-chat', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
