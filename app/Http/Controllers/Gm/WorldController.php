<?php

namespace App\Http\Controllers\Gm;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gm\RevealContentRequest;
use App\Http\Requests\Gm\StoreNpcRequest;
use App\Models\GameMap;
use App\Models\Npc;
use App\Models\User;
use App\Services\WorldContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorldController extends Controller
{
    public function index(): View
    {
        $maps = GameMap::query()->with('discoveredBy')->withCount('cellReveals')->orderBy('sort_order')->get();
        $players = User::query()->where('role', UserRole::Player->value)->orderBy('name')->get();

        return view('gm.world.index', compact('maps', 'players'));
    }

    public function updateMap(StoreMapRequest $request, GameMap $map, WorldContentService $service): RedirectResponse
    {
        $service->update($map, $request->payload());

        return back()->with('success', 'Carte mise à jour.');
    }

    public function storeNpc(StoreNpcRequest $request, WorldContentService $service): RedirectResponse
    {
        $service->createNpc($request->validated());

        return back()->with('success', 'PNJ créé.');
    }

    public function updateNpc(StoreNpcRequest $request, Npc $npc, WorldContentService $service): RedirectResponse
    {
        $service->update($npc, $request->validated());

        return back()->with('success', 'PNJ mis à jour.');
    }

    public function revealMap(RevealContentRequest $request, GameMap $map, WorldContentService $service): RedirectResponse
    {
        $service->revealMap($map, $this->recipient($request));

        return back()->with('success', 'Carte révélée.');
    }

    public function revealNpc(RevealContentRequest $request, Npc $npc, WorldContentService $service): RedirectResponse
    {
        $service->revealNpc($npc, $this->recipient($request));

        return back()->with('success', 'PNJ révélé.');
    }

    private function recipient(RevealContentRequest $request): ?User
    {
        return $request->validated('scope') === 'individual'
            ? User::query()->findOrFail($request->integer('user_id'))
            : null;
    }
}
