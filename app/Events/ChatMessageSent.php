<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message, public int $recipientId) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat-message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'body' => $this->message->body,
            'sent_at' => $this->message->created_at->toIso8601String(),
            'sent_at_label' => $this->message->created_at->format('H:i'),
        ];
    }
}
