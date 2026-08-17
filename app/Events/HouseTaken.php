<?php

namespace App\Events;

use App\Models\House;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Une grande maison vient d'être choisie.
 *
 * Diffusé à toute la table pour que la carte correspondante se grise
 * instantanément chez les joueurs encore en train de choisir. Le payload ne
 * contient que le slug et le prénom du joueur : rien de confidentiel ne
 * transite sur ce canal, qui est le seul partagé par tous les comptes.
 *
 * Ce message n'est qu'un confort d'affichage — l'exclusivité réelle est
 * garantie en base par HouseAssignmentService::claim(), qui verrouille la
 * ligne. Un joueur qui rate l'événement se verra simplement refuser son choix.
 */
class HouseTaken implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public House $house,
        public string $takenBy,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('houses')];
    }

    public function broadcastAs(): string
    {
        return 'house.taken';
    }

    public function broadcastWith(): array
    {
        return [
            'slug' => $this->house->slug,
            'name' => $this->house->name,
            'taken_by' => $this->takenBy,
        ];
    }
}
