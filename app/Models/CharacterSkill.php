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
 * Donnée personnage d'une compétence secondaire.
 *
 * La valeur finale n'est pas stockée : elle est recalculée par
 * StatFormulaService à partir des caractéristiques et du bonus personnel, pour
 * qu'un changement de formule s'applique immédiatement à toutes les fiches.
 */
#[Fillable(['character_id', 'skill_definition_id', 'bonus', 'reveal_state', 'gm_notes'])]
class CharacterSkill extends Model implements RevealableSheetEntry
{
    use Revealable;

    public function revealHeadline(): string
    {
        return $this->definition?->name ?? 'Compétence';
    }

    public function revealDescription(): string
    {
        return 'Tu prends conscience de ce que tu sais faire.';
    }

    public function revealKind(): string
    {
        return 'Compétence';
    }

    protected function casts(): array
    {
        return [
            'bonus' => 'integer',
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
        return $this->belongsTo(SkillDefinition::class, 'skill_definition_id');
    }
}
