<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une case de quadrillage ouverte aux joueurs.
 *
 * Liste blanche assumée : l'absence de ligne vaut « case dans le noir ».
 */
#[Fillable(['map_id', 'column', 'row'])]
class MapCellReveal extends Model
{
    protected function casts(): array
    {
        return ['column' => 'integer', 'row' => 'integer'];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(GameMap::class, 'map_id');
    }
}
