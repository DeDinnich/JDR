<?php

namespace App\Models;

use App\Enums\AbilityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Définition d'une capacité : sort, technique martiale, capacité spéciale,
 * talent... Le champ `details` (JSON) reste ouvert pour tout ce que nous
 * voudrons ajouter plus tard sans migration (portée, durée, composantes...).
 */
#[Fillable([
    'character_id', 'code', 'name', 'description', 'type', 'mastery_definition_id',
    'minimum_rank_index', 'mana_cost', 'details', 'sort_order', 'is_active',
])]
class AbilityDefinition extends Model
{
    protected function casts(): array
    {
        return [
            'type' => AbilityType::class,
            'minimum_rank_index' => 'integer',
            'mana_cost' => 'integer',
            'details' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function availableTo(Builder $query, Character $character): void
    {
        $query->where(fn (Builder $sub) => $sub
            ->whereNull('character_id')
            ->orWhere('character_id', $character->id));
    }

    public function masteryDefinition(): BelongsTo
    {
        return $this->belongsTo(MasteryDefinition::class);
    }

    public function characterAbilities(): HasMany
    {
        return $this->hasMany(CharacterAbility::class);
    }

    public function minimumRankLabel(): ?string
    {
        if ($this->minimum_rank_index === null) {
            return null;
        }

        return config('jdr.character.mastery_ranks')[$this->minimum_rank_index] ?? null;
    }
}
