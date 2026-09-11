<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name', 'type', 'description', 'dice_formula', 'mana_cost', 'cooldown', 'sort_order',
])]
class MonsterAbility extends Model
{
    protected function casts(): array
    {
        return [
            'mana_cost' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }
}
