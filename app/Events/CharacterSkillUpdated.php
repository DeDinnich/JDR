<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CharacterSkillUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $characterId, private readonly array $skill) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('characters.'.$this->characterId)];
    }

    public function broadcastAs(): string
    {
        return 'character-skill.updated';
    }

    public function broadcastWith(): array
    {
        return $this->skill;
    }
}
