<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Objet porté par un personnage.
 *
 * `is_visible_to_player` couvre l'objet que le personnage transporte sans le
 * savoir — une lettre cousue dans une doublure, par exemple. Un objet invisible
 * ne part pas dans le payload joueur et n'est ni modifiable ni supprimable par
 * lui : il n'est pas censé en connaître l'existence.
 */
#[Fillable(['character_id', 'name', 'category', 'description', 'quantity', 'equipped', 'is_visible_to_player'])]
class InventoryItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'equipped' => 'boolean',
            'is_visible_to_player' => 'boolean',
        ];
    }

    /** Objets que le joueur a le droit de voir et de manipuler. */
    public function scopeVisibleToPlayer(Builder $query): Builder
    {
        return $query->where('is_visible_to_player', true);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
