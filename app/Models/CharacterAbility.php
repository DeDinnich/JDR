<?php

namespace App\Models;

use App\Contracts\RevealableSheetEntry;
use App\Enums\RevealState;
use App\Models\Concerns\Revealable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capacité effectivement rattachée à un personnage.
 *
 * `unlocked` et `reveal_state` sont deux choses distinctes : un personnage
 * peut posséder une capacité (unlocked) sans que le joueur le sache encore
 * (reveal_state = hidden), typiquement un don qui se révélera en jeu.
 */
#[Fillable(['character_id', 'ability_definition_id', 'unlocked', 'reveal_state', 'gm_notes'])]
class CharacterAbility extends Model implements RevealableSheetEntry
{
    use Revealable;

    public function revealHeadline(): string
    {
        return $this->definition?->name ?? 'Capacité';
    }

    public function revealDescription(): string
    {
        return $this->definition?->description
            ?: 'Une capacité nouvelle s’ouvre à toi.';
    }

    public function revealKind(): string
    {
        return $this->definition?->type->label() ?? 'Capacité';
    }

    protected function casts(): array
    {
        return [
            'unlocked' => 'boolean',
            'reveal_state' => RevealState::class,
        ];
    }

    #[Scope]
    protected function discovered(Builder $query): void
    {
        $query->whereIn('reveal_state', [RevealState::Approximate->value, RevealState::Revealed->value]);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(AbilityDefinition::class, 'ability_definition_id');
    }
}
