<?php

namespace App\Http\Controllers\Gm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gm\MonsterAbilityRequest;
use App\Http\Requests\Gm\MonsterRequest;
use App\Http\Requests\Gm\RevealMonsterRequest;
use App\Models\Monster;
use App\Models\MonsterAbility;
use App\Services\Bestiary\DiceRoller;
use App\Services\Bestiary\MonsterImportService;
use App\Services\Bestiary\MonsterPortraitService;
use App\Services\Bestiary\MonsterRevealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonsterController extends Controller
{
    public function index(Request $request, MonsterRevealService $reveal): View
    {
        $search = trim((string) $request->query('recherche'));

        return view('gm.bestiary.index', [
            'monsters' => Monster::query()
                ->withCount('discoveredBy')
                ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('habitat', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->get(),
            'playersCount' => $reveal->audience()->count(),
            'search' => $search,
        ]);
    }

    public function show(Monster $monster, MonsterRevealService $reveal): View
    {
        $monster->load('abilities', 'discoveredBy');

        return view('gm.bestiary.show', [
            'monster' => $monster,
            'players' => $reveal->audience(),
        ]);
    }

    public function store(MonsterRequest $request, MonsterPortraitService $portraits): RedirectResponse
    {
        $monster = Monster::create($request->payload());

        if ($request->hasFile('portrait')) {
            $portraits->replace($monster, $request->file('portrait'));
        }

        return redirect()->route('gm.bestiary.show', $monster)->with('success', 'Monstre créé.');
    }

    public function update(MonsterRequest $request, Monster $monster, MonsterPortraitService $portraits): RedirectResponse
    {
        $monster->update($request->payload());

        if ($request->hasFile('portrait')) {
            $portraits->replace($monster, $request->file('portrait'));
        }

        return back()->with('success', 'Monstre mis à jour.');
    }

    public function destroy(Monster $monster, MonsterPortraitService $portraits): RedirectResponse
    {
        $portraits->remove($monster);
        $monster->delete();

        return redirect()->route('gm.bestiary.index')->with('success', 'Monstre supprimé.');
    }

    public function destroyPortrait(Monster $monster, MonsterPortraitService $portraits): RedirectResponse
    {
        $portraits->remove($monster);

        return back()->with('success', 'Portrait retiré.');
    }

    public function storeAbility(MonsterAbilityRequest $request, Monster $monster): RedirectResponse
    {
        $monster->abilities()->create($request->validated());

        return back()->with('success', 'Capacité ajoutée.');
    }

    public function updateAbility(
        MonsterAbilityRequest $request,
        Monster $monster,
        MonsterAbility $ability,
    ): RedirectResponse {
        $this->ensureAbilityOwnership($monster, $ability);
        $ability->update($request->validated());

        return back()->with('success', 'Capacité mise à jour.');
    }

    public function destroyAbility(Monster $monster, MonsterAbility $ability): RedirectResponse
    {
        $this->ensureAbilityOwnership($monster, $ability);
        $ability->delete();

        return back()->with('success', 'Capacité supprimée.');
    }

    public function rollAbility(Monster $monster, MonsterAbility $ability, DiceRoller $dice): JsonResponse
    {
        $this->ensureAbilityOwnership($monster, $ability);
        abort_if(blank($ability->dice_formula), 422, 'Cette capacité ne possède aucune formule de dés.');

        return response()->json($dice->roll($ability->dice_formula));
    }

    public function rollDamage(Monster $monster, DiceRoller $dice): JsonResponse
    {
        abort_if(blank($monster->damage_dice), 422, 'Aucune formule de dégâts configurée.');

        return response()->json($dice->roll($monster->damage_dice));
    }

    public function reveal(
        RevealMonsterRequest $request,
        Monster $monster,
        MonsterRevealService $reveal,
    ): RedirectResponse {
        $count = $reveal->reveal($monster, $request->userIds(), $request->boolean('all_players'));

        return back()->with('success', $count > 0
            ? "Monstre ajouté au bestiaire de {$count} joueur(s)."
            : 'Les joueurs sélectionnés connaissent déjà ce monstre.');
    }

    public function export(MonsterImportService $service, ?Monster $monster = null): JsonResponse
    {
        $monsters = $monster instanceof Monster
            ? collect([$monster->load('abilities')])
            : Monster::query()->with('abilities')->orderBy('name')->get();

        return response()->json($service->export($monsters), 200, [
            'Content-Disposition' => 'attachment; filename="bestiaire.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function ensureAbilityOwnership(Monster $monster, MonsterAbility $ability): void
    {
        abort_unless($ability->monster_id === $monster->id, 404);
    }
}
