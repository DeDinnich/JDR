<?php

namespace App\Events;

use App\Models\Character;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Le MJ vient d'ouvrir au joueur une donnée de sa fiche.
 *
 * Réutilise l'infrastructure Reverb déjà en place pour les messages secrets :
 * même canal privé par utilisateur, même mécanique côté front. Le payload ne
 * contient que ce qui vient précisément d'être révélé.
 */
class CharacterSheetRevealed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Character $character,
        public string $kind,
        public string $headline,
        public string $description,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->character->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'character-sheet.revealed';
    }

    public function broadcastWith(): array
    {
        return [
            'kind' => $this->kind,
            'headline' => $this->headline,
            'description' => $this->description,
            'revealed_at' => now()->toIso8601String(),
        ];
    }
}
