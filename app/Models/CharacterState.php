<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * État temporaire ou permanent affectant un personnage (Blessé, Béni, Maudit...).
 *
 * Le MJ peut poser un état invisible au joueur : une malédiction agit sans que
 * l'enfant comprenne ce qui lui arrive.
 */
#[Fillable([
    'character_id', 'name', 'description', 'icon', 'duration_label',
    'visible_to_player', 'is_active', 'modifiers',
])]
class CharacterState extends Model
{
    protected function casts(): array
    {
        return [
            'visible_to_player' => 'boolean',
            'is_active' => 'boolean',
            'modifiers' => 'array',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function visibleToPlayer(Builder $query): void
    {
        $query->where('visible_to_player', true);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /** Modificateurs résumés en une ligne lisible : « FOR -1 · DEX -2 ». */
    public function modifierSummary(): string
    {
        return collect($this->modifiers ?? [])
            ->map(fn ($value, $code) => mb_strtoupper($code).' '.($value >= 0 ? '+' : '').$value)
            ->implode(' · ');
    }
}
