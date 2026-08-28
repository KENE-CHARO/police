<?php

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes();

Broadcast::channel('users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
