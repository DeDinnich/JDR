<?php

namespace App\Events;

use App\Models\Character;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CharacterResourcesUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Character $character, private readonly array $resources) {}

    public static function from(Character $character, CharacterSheetPresenter $presenter): self
    {
        $character->loadMissing(CharacterSheetPresenter::RELATIONS);

        return new self($character, $presenter->forGameMaster($character)['resources']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('characters.'.$this->character->id),
            // Les ressources sont déjà visibles par toute la tablée sur les
            // fiches alliées. Ce canal les synchronise depuis n'importe quelle page.
            new PrivateChannel('table'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'character-resources.updated';
    }

    public function broadcastWith(): array
    {
        return ['character_id' => $this->character->id, ...$this->resources];
    }
}
