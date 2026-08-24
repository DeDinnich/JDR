<?php

namespace App\Events;

use App\Models\SecretMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SecretMessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable;

    public readonly int $messageId;

    public readonly int $recipientId;

    public function __construct(SecretMessage $message)
    {
        $this->messageId = $message->getKey();
        $this->recipientId = $message->recipient_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->recipientId),
            new PrivateChannel('game-masters'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'secret-message.deleted';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->messageId];
    }
}
