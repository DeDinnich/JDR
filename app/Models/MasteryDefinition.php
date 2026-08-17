<?php

namespace App\Models;

use App\Enums\MasteryCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Définition d'une maîtrise (Épée, Arc, Magie de l'eau...).
 *
 * Se situe entre les compétences et les techniques/sorts :
 * Caractéristiques → Compétences → Maîtrises → Techniques/Sorts.
 */
#[Fillable([
    'character_id', 'code', 'name', 'description', 'category',
    'magic_school_id', 'sort_order', 'is_active',
])]
class MasteryDefinition extends Model
{
    protected function casts(): array
    {
        return [
            'category' => MasteryCategory::class,
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

    public function magicSchool(): BelongsTo
    {
        return $this->belongsTo(MagicSchool::class);
    }

    public function abilityDefinitions(): HasMany
    {
        return $this->hasMany(AbilityDefinition::class);
    }

    public function characterMasteries(): HasMany
    {
        return $this->hasMany(CharacterMastery::class);
    }
}
