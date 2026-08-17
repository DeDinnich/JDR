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
 * Maîtrise d'un personnage, exprimée par un rang (Novice → Divin).
 *
 * Le rang est stocké sous forme d'index dans config('jdr.character.mastery_ranks')
 * afin que la liste des rangs reste modifiable sans migration.
 */
#[Fillable([
    'character_id', 'mastery_definition_id', 'rank_index', 'progress', 'reveal_state', 'gm_notes',
])]
class CharacterMastery extends Model implements RevealableSheetEntry
{
    use Revealable;

    public function revealHeadline(): string
    {
        return $this->definition?->name ?? 'Maîtrise';
    }

    public function revealDescription(): string
    {
        return 'Rang atteint : '.$this->rankLabel().'.';
    }

    public function revealKind(): string
    {
        return 'Maîtrise';
    }

    protected function casts(): array
    {
        return [
            'rank_index' => 'integer',
            'progress' => 'integer',
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
        return $this->belongsTo(MasteryDefinition::class, 'mastery_definition_id');
    }

    public function rankLabel(): string
    {
        $ranks = config('jdr.character.mastery_ranks');

        return $ranks[$this->rank_index] ?? ($ranks[0] ?? 'Novice');
    }

    public function isMaximumRank(): bool
    {
        return $this->rank_index >= count(config('jdr.character.mastery_ranks')) - 1;
    }
}
