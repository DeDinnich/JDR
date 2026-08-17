<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Définition d'une caractéristique principale (FOR, END, DEX, INT, CHA, MAN).
 *
 * Ajouter une caractéristique à la campagne = ajouter une entrée dans
 * config('jdr.character.attributes') puis relancer le seeder correspondant.
 */
#[Fillable(['code', 'name', 'abbreviation', 'description', 'sort_order'])]
class AttributeDefinition extends Model
{
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function characterAttributes(): HasMany
    {
        return $this->hasMany(CharacterAttribute::class);
    }

    /** Compétences dont cette caractéristique est la composante principale. */
    public function primarySkills(): HasMany
    {
        return $this->hasMany(SkillDefinition::class, 'primary_attribute_id');
    }

    public function secondarySkills(): HasMany
    {
        return $this->hasMany(SkillDefinition::class, 'secondary_attribute_id');
    }

    public function isMana(): bool
    {
        return $this->code === 'man';
    }
}
