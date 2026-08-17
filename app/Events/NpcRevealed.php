<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Le MJ vient de faire découvrir un PNJ — ou une information sur un PNJ — à un
 * joueur précis.
 *
 * Réutilise le canal privé par utilisateur déjà ouvert pour les messages
 * secrets et les révélations de fiche : aucune infrastructure temps réel
 * parallèle n'est introduite. Le payload ne contient que ce qui vient d'être
 * ouvert à CE joueur ; le filtrage reste fait côté serveur.
 */
class NpcRevealed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $userId,
        public string $kind,
        public string $headline,
        public string $description,
        public ?string $url = null,
        public ?string $actionLabel = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'npc.revealed';
    }

    public function broadcastWith(): array
    {
        return [
            'kind' => $this->kind,
            'headline' => $this->headline,
            'description' => $this->description,
            'url' => $this->url,
            'action_label' => $this->actionLabel,
            'revealed_at' => now()->toIso8601String(),
        ];
    }
}
