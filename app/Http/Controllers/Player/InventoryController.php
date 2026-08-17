<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\InventoryItemRequest;
use App\Http\Requests\Player\UpdateResourcesRequest;
use App\Models\Character;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inventaire côté joueur.
 *
 * Les objets que le personnage transporte sans le savoir sont écartés dès la
 * requête : ils ne sont ni affichés, ni comptés dans la charge, ni présents
 * dans le HTML. Le joueur ne peut donc pas non plus les modifier — toutes les
 * écritures passent par ownedItem(), qui ne résout que les objets visibles de
 * SON personnage.
 */
class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $character = $this->character($request);
        $items = $character->inventoryItems()->visibleToPlayer()->get();

        return view('player.inventory', [
            'character' => $character,
            'items' => $items,
            'groupedItems' => $items->groupBy('category'),
            'totalWeight' => $items->sum(fn (InventoryItem $item) => $item->totalWeight()),
        ]);
    }

    public function store(InventoryItemRequest $request): RedirectResponse
    {
        $this->character($request)->inventoryItems()->create($request->payload());

        return back()->with('success', 'Objet ajouté à ton inventaire.');
    }

    public function update(InventoryItemRequest $request, InventoryItem $item): RedirectResponse
    {
        $this->ownedItem($request, $item)->update($request->payload());

        return back()->with('success', 'Objet mis à jour.');
    }

    public function destroy(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->ownedItem($request, $item)->delete();

        return back()->with('success', 'Objet retiré de ton inventaire.');
    }

    /** PV, mana et bourse, tenus à jour par le joueur pendant la partie. */
    public function updateResources(UpdateResourcesRequest $request): RedirectResponse
    {
        $this->character($request)->update($request->validated());

        return back()->with('success', 'Ressources mises à jour.');
    }

    private function character(Request $request): Character
    {
        return $request->user()->character()->firstOrFail();
    }

    /**
     * Un objet invisible n'existe pas pour le joueur : tenter de le modifier
     * renvoie 404, comme s'il n'était pas là — et non 403, qui confirmerait
     * son existence.
     */
    private function ownedItem(Request $request, InventoryItem $item): InventoryItem
    {
        $character = $this->character($request);

        abort_unless(
            $item->character_id === $character->id && $item->is_visible_to_player,
            404,
        );

        return $item;
    }
}
