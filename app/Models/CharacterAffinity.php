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
 * Affinité naturelle d'un personnage pour une école de magie.
 *
 * Indépendante des maîtrises : on peut avoir une excellente affinité pour
 * l'eau sans avoir jamais travaillé la magie de l'eau.
 */
#[Fillable(['character_id', 'magic_school_id', 'affinity_level', 'reveal_state', 'gm_notes'])]
class CharacterAffinity extends Model implements RevealableSheetEntry
{
    use Revealable;

    public function revealHeadline(): string
    {
        return 'Affinité — '.($this->school?->name ?? 'École inconnue');
    }

    public function revealDescription(): string
    {
        return $this->reveal_state === RevealState::Revealed
            ? 'Affinité '.mb_strtolower($this->levelLabel()).'.'
            : 'Quelque chose en toi répond à cette magie.';
    }

    public function revealKind(): string
    {
        return 'Affinité magique';
    }

    protected function casts(): array
    {
        return [
            'affinity_level' => 'integer',
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

    public function school(): BelongsTo
    {
        return $this->belongsTo(MagicSchool::class, 'magic_school_id');
    }

    public function levelLabel(): string
    {
        $levels = config('jdr.character.affinity_levels');

        return $levels[$this->affinity_level] ?? ($levels[0] ?? 'Inconnue');
    }

    public function isUntested(): bool
    {
        return $this->affinity_level === 0;
    }
}
