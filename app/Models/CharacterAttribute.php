<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valeur d'une caractéristique pour un personnage donné.
 *
 * Volontairement simple : le MJ (ou le joueur) pose la valeur à la main, il
 * n'y a ni XP, ni prédisposition, ni progression automatique. Une
 * caractéristique n'est jamais cachée — si elle est sur la fiche, le joueur la
 * voit. Seules les compétences, maîtrises, affinités et capacités peuvent être
 * dissimulées pour servir la découverte narrative.
 */
#[Fillable(['character_id', 'attribute_definition_id', 'value', 'modifier'])]
class CharacterAttribute extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'modifier' => 'integer',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }

    /** Valeur effective : valeur posée + modificateur ponctuel (états). */
    public function effectiveValue(): int
    {
        return $this->value + $this->modifier;
    }
}
