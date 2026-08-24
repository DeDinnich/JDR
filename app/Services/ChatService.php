<?php

namespace App\Services;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /** Crée les fils 1/1 manquants pour tous les membres actuels de la table. */
    public function ensureConversationsFor(User $user): void
    {
        User::query()->whereKeyNot($user->getKey())->pluck('id')->each(
            fn (int $otherId) => $this->betweenIds($user->getKey(), $otherId)
        );
    }

    public function between(User $first, User $second): Conversation
    {
        abort_if($first->is($second), 422);

        return $this->betweenIds($first->getKey(), $second->getKey());
    }

    public function send(Conversation $conversation, User $sender, string $body): ChatMessage
    {
        abort_unless($conversation->includes($sender), 403);

        $recipient = $conversation->otherParticipant($sender);

        $message = DB::transaction(function () use ($conversation, $sender, $body): ChatMessage {
            $message = $conversation->messages()->create([
                'sender_id' => $sender->getKey(),
                'body' => trim($body),
            ]);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return $message;
        });

        $message->load('sender');
        broadcast(new ChatMessageSent($message, $recipient->getKey()))->toOthers();

        return $message;
    }

    public function markRead(Conversation $conversation, User $reader): void
    {
        abort_unless($conversation->includes($reader), 403);

        $conversation->messages()
            ->where('sender_id', '!=', $reader->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** @return Collection<int, Conversation> */
    public function listFor(User $user): Collection
    {
        $this->ensureConversationsFor($user);

        return Conversation::query()
            ->where(fn ($query) => $query
                ->where('participant_one_id', $user->getKey())
                ->orWhere('participant_two_id', $user->getKey()))
            ->with(['participantOne.character', 'participantTwo.character'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->where('sender_id', '!=', $user->getKey())
                ->whereNull('read_at')])
            ->orderByDesc('last_message_at')
            ->orderBy('id')
            ->get();
    }

    private function betweenIds(int $firstId, int $secondId): Conversation
    {
        [$one, $two] = $firstId < $secondId ? [$firstId, $secondId] : [$secondId, $firstId];

        return Conversation::query()->firstOrCreate([
            'participant_one_id' => $one,
            'participant_two_id' => $two,
        ]);
    }
}
