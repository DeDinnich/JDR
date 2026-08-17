<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function character(): HasOne
    {
        return $this->hasOne(Character::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function discoveredMaps(): BelongsToMany
    {
        return $this->belongsToMany(GameMap::class, 'map_user', 'user_id', 'map_id')->withPivot('discovered_at');
    }

    public function discoveredLocations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)->withPivot('discovered_at');
    }

    public function discoveredNpcs(): BelongsToMany
    {
        return $this->belongsToMany(Npc::class)
            ->withPivot(['relationship', 'personal_notes', 'discovered_at'])
            ->withTimestamps();
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(SecretMessage::class, 'recipient_id');
    }

    public function isGameMaster(): bool
    {
        return $this->role === UserRole::GameMaster;
    }
}
