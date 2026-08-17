<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grande maison noble ou origine familiale d'un personnage.
 *
 * `game_master_description` est réservée au MJ : elle ne doit jamais être
 * sérialisée vers un joueur. Utiliser publicPayload() pour tout affichage
 * destiné au joueur plutôt que de passer le modèle entier à une vue.
 */
#[Fillable([
    'slug', 'name', 'motto', 'public_description', 'game_master_description',
    'emblem_path', 'color', 'reputation', 'specialty', 'is_active',
    'is_reserved', 'sort_order',
])]
class House extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_reserved' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }

    /** Maisons pouvant entrer dans le tirage aléatoire des joueurs standards. */
    public function scopeAssignable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_reserved', false);
    }

    /** Une maison est prise dès qu'un personnage la porte. */
    public function isTaken(): bool
    {
        return $this->characters()->exists();
    }

    /**
     * Données transmissibles au joueur. Volontairement explicite : ajouter une
     * colonne secrète à la table ne la fera pas fuiter par accident.
     *
     * @return array<string, mixed>
     */
    public function publicPayload(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'motto' => $this->motto,
            'description' => $this->public_description,
            'emblem_path' => $this->emblem_path,
            'color' => $this->color,
            'reputation' => $this->reputation,
            'specialty' => $this->specialty,
        ];
    }
}
