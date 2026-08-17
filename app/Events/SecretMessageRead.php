<?php

namespace App\Events;

use App\Models\SecretMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SecretMessageRead implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public SecretMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('game-masters')];
    }

    public function broadcastAs(): string
    {
        return 'secret-message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'read_at' => $this->message->read_at?->toIso8601String(),
        ];
    }
}
