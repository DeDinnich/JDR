<?php

namespace App\Events;

use App\Models\SecretMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecretMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SecretMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->message->recipient_id)];
    }

    public function broadcastAs(): string
    {
        return 'secret-message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'body' => $this->message->body,
            'priority' => $this->message->priority,
            'sent_at' => $this->message->created_at->toIso8601String(),
            'delete_url' => route('messages.destroy', $this->message),
        ];
    }
}
