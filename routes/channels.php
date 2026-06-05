<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Canal público para alertas (stock bajo, vencimiento)
// Sin autenticación - cualquier usuario autenticado puede suscribirse
Broadcast::channel('alerts', function (User $user) {
    return true;
});

// Canal privado por usuario (notificaciones personales)
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
});

// Canal para dashboard (actualizaciones en tiempo real)
Broadcast::channel('dashboard', function (User $user) {
    return true;
});
