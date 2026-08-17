<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Personnage joueur.
 *
 * Particularité de cette campagne : le personnage commence bébé. Presque tout
 * est donc facultatif à la création (classe, race, profession...) et se
 * remplira au fil de l'enfance. Les valeurs chiffrées de la fiche vivent dans
 * les tables character_* et les calculs dans App\Services\CharacterSheet.
 */
#[Fillable([
    'user_id', 'house_id', 'current_map_id', 'name', 'first_name', 'last_name', 'nickname',
    'portrait_path', 'gender', 'birth_date', 'age_years', 'archetype', 'ancestry',
    'origin', 'current_location', 'occupation', 'adventurer_title', 'background',
    'health', 'max_health', 'mana_current', 'mana_max',
    'armor', 'gold', 'status', 'biography', 'traits',
])]
class Character extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'age_years' => 'integer',
            'health' => 'integer',
            'max_health' => 'integer',
            'mana_current' => 'integer',
            'mana_max' => 'integer',
            'armor' => 'integer',
            'gold' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentMap(): BelongsTo
    {
        return $this->belongsTo(GameMap::class, 'current_map_id');
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    /** Parents et proches rattachés nominativement à cet enfant. */
    public function relatives(): BelongsToMany
    {
        return $this->belongsToMany(Npc::class)->withPivot('relation');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CharacterAttribute::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CharacterSkill::class);
    }

    public function masteries(): HasMany
    {
        return $this->hasMany(CharacterMastery::class);
    }

    public function affinities(): HasMany
    {
        return $this->hasMany(CharacterAffinity::class);
    }

    public function abilities(): HasMany
    {
        return $this->hasMany(CharacterAbility::class);
    }

    public function states(): HasMany
    {
        return $this->hasMany(CharacterState::class)->orderByDesc('is_active')->orderBy('name');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class)->orderByDesc('equipped')->orderBy('category')->orderBy('name');
    }

    /** Nom affiché : nom complet si renseigné, sinon le nom historique. */
    public function displayName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : $this->name;
    }

    public function healthPercentage(): int
    {
        return $this->max_health > 0 ? (int) round(($this->health / $this->max_health) * 100) : 0;
    }

    /** Âge lisible, y compris pour un nouveau-né. */
    public function ageLabel(): string
    {
        return match (true) {
            $this->age_years === 0 => 'Nouveau-né',
            $this->age_years === 1 => '1 an',
            default => $this->age_years.' ans',
        };
    }
}
