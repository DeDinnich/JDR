<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['participant_one_id', 'participant_two_id', 'last_message_at'])]
class Conversation extends Model
{
    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function participantOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_one_id');
    }

    public function participantTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }

    public function includes(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $this->participant_one_id === $userId || $this->participant_two_id === $userId;
    }

    public function otherParticipant(User $user): User
    {
        abort_unless($this->includes($user), 403);

        return $this->participant_one_id === $user->getKey()
            ? $this->participantTwo
            : $this->participantOne;
    }
}
