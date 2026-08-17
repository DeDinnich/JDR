<?php

namespace App\Models;

use App\Enums\SkillCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Définition d'une compétence secondaire.
 *
 * Une compétence est calculée à partir d'une ou deux caractéristiques ; la
 * formule elle-même vit dans StatFormulaService, jamais dans les vues.
 * `character_id` à NULL = compétence commune à toute la campagne.
 */
#[Fillable([
    'character_id', 'code', 'name', 'description', 'category',
    'primary_attribute_id', 'secondary_attribute_id', 'formula', 'sort_order', 'is_active',
])]
class SkillDefinition extends Model
{
    protected function casts(): array
    {
        return [
            'category' => SkillCategory::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Définitions communes + définitions sur mesure de ce personnage. */
    #[Scope]
    protected function availableTo(Builder $query, Character $character): void
    {
        $query->where(fn (Builder $sub) => $sub
            ->whereNull('character_id')
            ->orWhere('character_id', $character->id));
    }

    public function primaryAttribute(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'primary_attribute_id');
    }

    public function secondaryAttribute(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'secondary_attribute_id');
    }

    public function characterSkills(): HasMany
    {
        return $this->hasMany(CharacterSkill::class);
    }

    /** Libellé du type « FOR / DEX » affiché sous le nom de la compétence. */
    public function attributeLabel(): string
    {
        return collect([$this->primaryAttribute, $this->secondaryAttribute])
            ->filter()
            ->map(fn (AttributeDefinition $attribute) => $attribute->abbreviation)
            ->implode(' / ');
    }
}
