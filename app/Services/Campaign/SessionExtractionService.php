<?php

namespace App\Services\Campaign;

use App\Enums\UserRole;
use App\Models\GameMap;
use App\Models\Location;
use App\Models\MapPoint;
use App\Models\SecretMessage;
use App\Models\User;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use Illuminate\Support\Facades\DB;

/**
 * Produit une sauvegarde partageable avec un assistant narratif.
 *
 * Le snapshot reprend exclusivement ce que chaque joueur connaît au moment de
 * l'extraction. Les notes MJ et les éléments encore cachés ne sont donc jamais
 * sérialisés, même si l'export est déclenché depuis l'espace MJ.
 */
class SessionExtractionService
{
    public function __construct(
        private readonly CharacterSheetPresenter $characterSheets,
        private readonly NpcPresenter $npcs,
    ) {}

    /** @param array<int, int|string> $userIds */
    public function extract(array $userIds): array
    {
        return DB::transaction(function () use ($userIds): array {
            $players = User::query()
                ->where('role', UserRole::Player->value)
                ->whereKey($userIds)
                ->with('character')
                ->orderBy('name')
                ->get();

            return [
                'schema_version' => '1.0',
                'campaign' => config('app.name'),
                'extracted_at' => now()->toIso8601String(),
                'scope' => 'Connaissances accessibles aux joueurs sélectionnés au moment de l’extraction.',
                'players' => $players->map(fn (User $player) => $this->playerSnapshot($player))->all(),
            ];
        });
    }

    private function playerSnapshot(User $player): array
    {
        $character = $player->character;

        if ($character !== null) {
            $character->loadMissing([...CharacterSheetPresenter::RELATIONS, 'house']);
        }

        return [
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
            ],
            'character_sheet' => $character === null ? null : [
                ...$this->characterSheets->forPlayer($character),
                'house' => $character->house?->publicPayload(),
            ],
            'notes' => $player->notes()
                ->orderByDesc('pinned')
                ->latest('updated_at')
                ->get()
                ->map(fn ($note) => [
                    'title' => $note->title,
                    'content' => $note->content,
                    'pinned' => $note->pinned,
                    'updated_at' => $note->updated_at?->toIso8601String(),
                ])
                ->all(),
            'glossary' => $this->npcs->glossaryFor($player),
            'world' => ['maps' => $this->knownMaps($player)],
            'received_secret_messages' => $player->receivedMessages()
                ->oldest()
                ->get()
                ->map(fn (SecretMessage $message) => [
                    'body' => $message->body,
                    'sent_at' => $message->created_at?->toIso8601String(),
                    'read_at' => $message->read_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function knownMaps(User $player): array
    {
        $locations = $player->discoveredLocations()
            ->orderBy('name')
            ->get()
            ->groupBy('map_id');

        return $player->discoveredMaps()
            ->with([
                'cellReveals:id,map_id,column,row',
                'points' => fn ($query) => $query
                    ->where(fn ($points) => $points
                        ->where('user_id', $player->getKey())
                        ->orWhere('is_visible_to_players', true))
                    ->with('author:id,name'),
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (GameMap $map) => [
                'title' => $map->title,
                'description' => $map->description,
                'discovered_at' => $map->pivot?->discovered_at,
                'revealed_cells' => $map->cellReveals
                    ->map(fn ($cell) => ['column' => $cell->column, 'row' => $cell->row])
                    ->values()
                    ->all(),
                'known_locations' => $locations->get($map->id, collect())
                    ->map(fn (Location $location) => [
                        'name' => $location->name,
                        'type' => $location->type,
                        'description' => $location->description,
                        'position' => [
                            'x' => (float) $location->x_position,
                            'y' => (float) $location->y_position,
                        ],
                        'discovered_at' => $location->pivot?->discovered_at,
                    ])
                    ->values()
                    ->all(),
                'visible_markers' => $map->points
                    ->map(fn (MapPoint $point) => [
                        'label' => $point->label,
                        'author' => $point->author?->name,
                        'position' => [
                            'x' => $point->x_position,
                            'y' => $point->y_position,
                        ],
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }
}
