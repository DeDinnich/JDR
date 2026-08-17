<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'slug', 'description', 'image_path', 'is_active', 'sort_order',
    'grid_columns', 'grid_rows', 'image_width', 'image_height',
])]
class GameMap extends Model
{
    protected $table = 'maps';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'grid_columns' => 'integer',
            'grid_rows' => 'integer',
        ];
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'map_id')->orderBy('name');
    }

    public function cellReveals(): HasMany
    {
        return $this->hasMany(MapCellReveal::class, 'map_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(MapPoint::class, 'map_id')->latest('id');
    }

    /**
     * Cases ouvertes, sous forme de clés « colonne:ligne ».
     *
     * Le rendu teste des milliers de cases : on veut une recherche en O(1),
     * pas une requête ou un parcours de collection par case.
     *
     * @return array<string, true>
     */
    public function revealedCellKeys(): array
    {
        return $this->cellReveals
            ->mapWithKeys(fn (MapCellReveal $cell) => [$cell->column.':'.$cell->row => true])
            ->all();
    }

    /** Une carte sans image n'a pas de quadrillage à afficher. */
    public function hasGrid(): bool
    {
        return $this->image_path !== null && $this->image_width !== null;
    }

    public function discoveredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'map_user', 'map_id', 'user_id')->withPivot('discovered_at');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
