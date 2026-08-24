<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
Broadcast::channel('game-masters', fn ($user) => $user->isGameMaster());
Broadcast::channel('characters.{characterId}', fn ($user, $characterId) => $user->isGameMaster() || (int) $user->character?->id === (int) $characterId
);
Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
    return Conversation::query()
        ->whereKey($conversationId)
        ->where(fn ($query) => $query
            ->where('participant_one_id', $user->getKey())
            ->orWhere('participant_two_id', $user->getKey()))
        ->exists();
});

// Canal public de la table : sert uniquement à griser une maison déjà prise.
// Rien de confidentiel n'y transite (voir App\Events\HouseTaken).
