<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MonsterKnowledge extends Pivot
{
    public $incrementing = false;

    protected $table = 'monster_user';

    protected function casts(): array
    {
        return [
            'discovered_at' => 'datetime',
        ];
    }
}
