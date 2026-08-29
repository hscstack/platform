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
// Only authenticated users join this channel; the data returned
// here is what other members see via .here() / .joining().
Broadcast::channel('presence-global-chat', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'image_url' => $user->image_url,
    ];
});
