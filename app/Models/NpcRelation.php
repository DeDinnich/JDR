<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lien narratif orienté entre deux PNJ (« père de », « sert », « rival de »). */
#[Fillable(['npc_id', 'related_npc_id', 'label', 'is_secret'])]
class NpcRelation extends Model
{
    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function relatedNpc(): BelongsTo
    {
        return $this->belongsTo(Npc::class, 'related_npc_id');
    }
}
