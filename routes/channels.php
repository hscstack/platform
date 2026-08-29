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
// Only registered when Pusher credentials are configured.
if (env('PUSHER_APP_KEY')) {
    Broadcast::channel('presence-global-chat', function ($user) {
        return ['id' => $user->id];
    });
}
