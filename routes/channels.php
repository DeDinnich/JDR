<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
Broadcast::channel('game-masters', fn ($user) => $user->isGameMaster());

// Canal public de la table : sert uniquement à griser une maison déjà prise.
// Rien de confidentiel n'y transite (voir App\Events\HouseTaken).
