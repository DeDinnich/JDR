<?php

namespace App\Services\Bestiary;

use App\Enums\UserRole;
use App\Events\MonsterRevealed;
use App\Models\Monster;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MonsterRevealService
{
    /** @return Collection<int, User> */
    public function audience(): Collection
    {
        return User::query()
            ->where('role', UserRole::Player->value)
            ->orderBy('name')
            ->get();
    }

    /** @param array<int, int> $userIds */
    public function reveal(Monster $monster, array $userIds, bool $toEveryPlayer = false): int
    {
        $users = $toEveryPlayer
            ? $this->audience()
            : User::query()
                ->where('role', UserRole::Player->value)
                ->whereKey($userIds)
                ->get();
        $alreadyKnown = $monster->discoveredBy()->pluck('users.id')->all();
        $newPlayers = $users->reject(fn (User $user) => in_array($user->id, $alreadyKnown, true));

        DB::transaction(function () use ($monster, $newPlayers): void {
            foreach ($newPlayers as $user) {
                $monster->discoveredBy()->attach($user->id, ['discovered_at' => now()]);
            }
        });

        foreach ($newPlayers as $user) {
            MonsterRevealed::dispatch(
                $user->id,
                $monster->name,
                $monster->description,
                route('player.bestiary.show', $monster),
            );
        }

        return $newPlayers->count();
    }
}
