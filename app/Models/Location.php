<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['map_id', 'name', 'type', 'description', 'x_position', 'y_position'])]
class Location extends Model
{
    protected function casts(): array
    {
        return ['x_position' => 'decimal:2', 'y_position' => 'decimal:2'];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(GameMap::class, 'map_id');
    }

    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class)->orderBy('name');
    }

    public function discoveredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('discovered_at');
    }
}
