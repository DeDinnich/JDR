<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Secret MJ attaché à un PNJ.
 *
 * Ce modèle n'est jamais chargé dans un contexte joueur : aucune méthode de
 * présentation joueur ne le référence, et aucune route joueur ne l'expose.
 */
#[Fillable(['npc_id', 'title', 'content', 'sort_order'])]
class NpcSecret extends Model
{
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
