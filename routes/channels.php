<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('online-users', function ($user) {
    dd(['user_status' => $user ? 'authenticated' : 'not_authenticated', 'user_id' => $user ? $user->id : null]);
});

// Hapus semua definisi channel lain untuk sementara, termasuk 'App.Models.User.{id}'