<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'type', 'size', 'threat_level', 'description', 'habitat', 'behavior',
    'game_master_notes', 'portrait_path', 'health_current', 'health_max',
    'mana_current', 'mana_max', 'armor', 'strength', 'endurance', 'dexterity',
    'intelligence', 'willpower', 'perception', 'damage_dice', 'weakness',
])]
class Monster extends Model
{
    protected function casts(): array
    {
        return [
            'health_current' => 'integer',
            'health_max' => 'integer',
            'mana_current' => 'integer',
            'mana_max' => 'integer',
            'armor' => 'integer',
            'strength' => 'integer',
            'endurance' => 'integer',
            'dexterity' => 'integer',
            'intelligence' => 'integer',
            'willpower' => 'integer',
            'perception' => 'integer',
        ];
    }

    public function abilities(): HasMany
    {
        return $this->hasMany(MonsterAbility::class)->orderBy('sort_order')->orderBy('id');
    }

    public function discoveredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(MonsterKnowledge::class)
            ->withPivot([
                'discovered_at', 'known_health', 'known_mana', 'known_abilities',
                'known_damage', 'known_weakness', 'personal_notes',
            ])
            ->withTimestamps();
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');
    }

    public function isKnownBy(User $user): bool
    {
        return $this->discoveredBy()->whereKey($user->getKey())->exists();
    }
}
