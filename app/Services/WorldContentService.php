<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\GameMap;
use App\Models\Location;
use App\Models\Npc;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WorldContentService
{
    public function createMap(array $data): GameMap
    {
        return GameMap::create($data);
    }

    public function createLocation(array $data): Location
    {
        return Location::create($data);
    }

    public function createNpc(array $data): Npc
    {
        return Npc::create($data);
    }

    public function update(Model $content, array $data): Model
    {
        $content->update($data);

        return $content->refresh();
    }

    public function revealMap(GameMap $map, ?User $recipient): void
    {
        $map->discoveredBy()->syncWithoutDetaching($this->recipientIds($recipient));
    }

    public function revealLocation(Location $location, ?User $recipient): void
    {
        $recipientIds = $this->recipientIds($recipient);

        $location->map->discoveredBy()->syncWithoutDetaching($recipientIds);
        $location->discoveredBy()->syncWithoutDetaching($recipientIds);
    }

    public function revealNpc(Npc $npc, ?User $recipient): void
    {
        $recipientIds = $this->recipientIds($recipient);

        if ($npc->location) {
            $npc->location->map->discoveredBy()->syncWithoutDetaching($recipientIds);
            $npc->location->discoveredBy()->syncWithoutDetaching($recipientIds);
        }

        $npc->discoveredBy()->syncWithoutDetaching(
            $recipientIds->mapWithKeys(fn (int $id) => [$id => ['relationship' => 'neutre']])->all()
        );
    }

    private function recipientIds(?User $recipient): Collection
    {
        if ($recipient) {
            abort_unless($recipient->role === UserRole::Player, 422, 'Le destinataire doit être un joueur.');

            return collect([$recipient->id]);
        }

        return User::query()->where('role', UserRole::Player->value)->pluck('id');
    }
}
