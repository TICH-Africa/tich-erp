<?php

use App\Services\RBACService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('hr.sidebar', function ($user) {
    if (! $user) {
        return false;
    }

    return app(RBACService::class)->hasPermission($user, 'hr.staff.view');
});
