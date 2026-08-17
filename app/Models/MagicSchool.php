<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * École de magie (Feu, Eau, Terre...). Sert de support aux affinités d'un
 * personnage et, éventuellement, à ses maîtrises magiques.
 *
 * Ajouter une école plus tard = une simple ligne, aucune migration.
 */
#[Fillable(['code', 'name', 'description', 'color', 'sort_order', 'is_active'])]
class MagicSchool extends Model
{
    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function affinities(): HasMany
    {
        return $this->hasMany(CharacterAffinity::class);
    }

    public function masteryDefinitions(): HasMany
    {
        return $this->hasMany(MasteryDefinition::class);
    }
}
