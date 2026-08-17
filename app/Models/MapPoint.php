<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Repère posé sur une carte.
 *
 * Chaque point appartient à celui qui l'a posé. Un joueur ne voit que les
 * siens et ceux que le MJ a ouverts ; le MJ voit tout et choisit quels joueurs
 * afficher.
 */
#[Fillable(['map_id', 'user_id', 'label', 'color', 'x_position', 'y_position', 'is_visible_to_players'])]
class MapPoint extends Model
{
    protected function casts(): array
    {
        return [
            'x_position' => 'float',
            'y_position' => 'float',
            'is_visible_to_players' => 'boolean',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(GameMap::class, 'map_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
