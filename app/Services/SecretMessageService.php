<?php

namespace App\Services;

use App\Events\SecretMessageRead;
use App\Events\SecretMessageSent;
use App\Models\SecretMessage;
use App\Models\User;

class SecretMessageService
{
    public function send(User $sender, User $recipient, array $data): SecretMessage
    {
        $message = SecretMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => $data['body'],
        ]);

        SecretMessageSent::dispatch($message);

        return $message;
    }

    public function markAsRead(SecretMessage $message): SecretMessage
    {
        if ($message->read_at === null) {
            $message->forceFill(['read_at' => now()])->save();
            SecretMessageRead::dispatch($message);
        }

        return $message;
    }
}
