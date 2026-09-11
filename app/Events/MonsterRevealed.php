<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MonsterRevealed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $userId,
        public readonly string $monsterName,
        public readonly ?string $description,
        public readonly string $url,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'monster.revealed';
    }

    public function broadcastWith(): array
    {
        return [
            'kind' => 'Nouvelle créature',
            'headline' => $this->monsterName,
            'description' => $this->description ?: 'Cette créature rejoint votre bestiaire.',
            'url' => $this->url,
            'action_label' => 'Ouvrir le bestiaire',
            'revealed_at' => now()->toIso8601String(),
        ];
    }
}
