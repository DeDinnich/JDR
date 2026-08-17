<?php

namespace App\Models;

use App\Enums\NpcImportance;
use App\Enums\NpcStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Personnage non joueur.
 *
 * Le modèle porte à la fois des données publiques (`description`) et des
 * données strictement MJ (`game_master_notes`, relation `secrets`). Rien ne
 * doit partir vers un joueur sans passer par NpcPresenter : c'est lui qui
 * décide, information par information, de ce que ce joueur précis connaît.
 */
#[Fillable([
    'location_id', 'house_id', 'name', 'first_name', 'last_name', 'nickname',
    'title', 'age', 'gender', 'race', 'profession', 'family_role', 'role',
    'description', 'personality', 'status', 'importance', 'tags',
    'game_master_notes', 'portrait_path',
])]
class Npc extends Model
{
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'tags' => 'array',
            'status' => NpcStatus::class,
            'importance' => NpcImportance::class,
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function secrets(): HasMany
    {
        return $this->hasMany(NpcSecret::class)->orderBy('sort_order');
    }

    public function informations(): HasMany
    {
        return $this->hasMany(NpcInformation::class)->orderBy('sort_order');
    }

    /** Nommée `npcRelations` et non `relations` : Model réserve déjà ce nom. */
    public function npcRelations(): HasMany
    {
        return $this->hasMany(NpcRelation::class);
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class)->withPivot('relation');
    }

    public function discoveredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['relationship', 'personal_notes', 'discovered_at'])
            ->withTimestamps();
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->map(fn (string $part) => mb_substr($part, 0, 1))->take(2)->implode('');
    }

    /** Nom complet reconstruit, avec repli sur le nom historique. */
    public function fullName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : $this->name;
    }

    public function isKnownBy(User $user): bool
    {
        return $this->discoveredBy()->whereKey($user->getKey())->exists();
    }
}
