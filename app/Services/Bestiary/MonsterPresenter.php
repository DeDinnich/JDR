<?php

namespace App\Services\Bestiary;

use App\Models\Monster;
use App\Models\User;

/** Ne laisse sortir que l'apparence publique et les déductions du joueur. */
class MonsterPresenter
{
    /** @return array<string, mixed>|null */
    public function forPlayer(Monster $monster, User $user): ?array
    {
        $knownMonster = $monster->relationLoaded('discoveredBy')
            ? $monster->discoveredBy->firstWhere('id', $user->getKey())
            : $monster->discoveredBy()->whereKey($user->getKey())->first();
        $knowledge = $knownMonster?->pivot;

        if ($knowledge === null) {
            return null;
        }

        return [
            'id' => $monster->id,
            'name' => $monster->name,
            'type' => $monster->type,
            'size' => $monster->size,
            'description' => $monster->description,
            'portrait_path' => $monster->portrait_path,
            'initials' => $monster->initials(),
            'discovered_at' => $knowledge->discovered_at,
            'knowledge' => [
                'health' => $knowledge->known_health,
                'mana' => $knowledge->known_mana,
                'abilities' => $knowledge->known_abilities,
                'damage' => $knowledge->known_damage,
                'weakness' => $knowledge->known_weakness,
                'notes' => $knowledge->personal_notes,
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function bestiaryFor(User $user): array
    {
        return Monster::query()
            ->whereHas('discoveredBy', fn ($query) => $query->whereKey($user->getKey()))
            ->with(['discoveredBy' => fn ($query) => $query->whereKey($user->getKey())])
            ->orderBy('name')
            ->get()
            ->map(fn (Monster $monster) => $this->forPlayer($monster, $user))
            ->filter()
            ->values()
            ->all();
    }
}
