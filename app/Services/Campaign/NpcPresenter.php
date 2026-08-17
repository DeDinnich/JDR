<?php

namespace App\Services\Campaign;

use App\Models\Npc;
use App\Models\User;

/**
 * Filtrage serveur des données PNJ.
 *
 * Règle unique et non négociable : rien de ce que le joueur n'a pas découvert
 * ne quitte le serveur. On ne masque jamais en CSS, on ne renvoie jamais un
 * modèle Npc brut à une vue joueur — c'est cette classe qui construit le
 * tableau exact que le joueur a le droit de lire.
 *
 * Les notes MJ, les secrets et les informations non révélées à CE joueur
 * n'apparaissent dans aucune des méthodes `forPlayer*`.
 */
class NpcPresenter
{
    /**
     * Fiche d'un PNJ telle que ce joueur la connaît.
     *
     * @return array<string, mixed>|null null si le joueur n'a pas encore
     *                                   rencontré ce PNJ.
     */
    public function forPlayer(Npc $npc, User $user): ?array
    {
        $pivot = $npc->discoveredBy()->whereKey($user->getKey())->first()?->pivot;

        if ($pivot === null) {
            return null;
        }

        return [
            'id' => $npc->id,
            'name' => $npc->fullName(),
            'nickname' => $npc->nickname,
            'portrait_path' => $npc->portrait_path,
            'initials' => $npc->initials(),
            // Relation et notes viennent du pivot : elles sont propres au joueur.
            'relationship' => $pivot->relationship,
            'personal_notes' => $pivot->personal_notes,
            'discovered_at' => $pivot->discovered_at,
            'known_location' => $npc->location?->name,
            'informations' => $this->revealedInformations($npc, $user),
        ];
    }

    /**
     * Glossaire : tous les PNJ rencontrés par ce joueur.
     *
     * @return array<int, array<string, mixed>>
     */
    public function glossaryFor(User $user): array
    {
        $npcs = Npc::query()
            ->whereHas('discoveredBy', fn ($query) => $query->whereKey($user->getKey()))
            ->with('location')
            ->orderBy('name')
            ->get();

        return $npcs
            ->map(fn (Npc $npc) => $this->forPlayer($npc, $user))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Informations effectivement révélées à ce joueur, dans l'ordre voulu par
     * le MJ. Une information non révélée n'est pas renvoyée du tout.
     *
     * @return array<int, array<string, mixed>>
     */
    private function revealedInformations(Npc $npc, User $user): array
    {
        return $npc->informations()
            ->whereHas('revealedTo', fn ($query) => $query->whereKey($user->getKey()))
            ->get()
            ->map(fn ($information) => [
                'title' => $information->title,
                'content' => $information->content,
                'category' => $information->category,
                'category_label' => $information->categoryLabel(),
            ])
            ->all();
    }
}
