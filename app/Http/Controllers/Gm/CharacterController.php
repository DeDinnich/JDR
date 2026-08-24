<?php

namespace App\Http\Controllers\Gm;

use App\Enums\RevealState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gm\CharacterAbilityRequest;
use App\Http\Requests\Gm\CharacterAffinityRequest;
use App\Http\Requests\Gm\CharacterMasteryRequest;
use App\Http\Requests\Gm\CharacterStateRequest;
use App\Http\Requests\Gm\InventoryItemRequest;
use App\Http\Requests\Gm\RevealSheetEntryRequest;
use App\Http\Requests\Gm\UpdateAttributeRequest;
use App\Http\Requests\Gm\UpdateCharacterRequest;
use App\Http\Requests\Gm\UpdateCharacterSkillRequest;
use App\Models\AbilityDefinition;
use App\Models\Character;
use App\Models\CharacterAbility;
use App\Models\CharacterAffinity;
use App\Models\CharacterAttribute;
use App\Models\CharacterMastery;
use App\Models\CharacterSkill;
use App\Models\CharacterState;
use App\Models\GameMap;
use App\Models\InventoryItem;
use App\Models\MasteryDefinition;
use App\Services\CharacterManagementService;
use App\Services\CharacterSheet\AttributeService;
use App\Services\CharacterSheet\CharacterRevealService;
use App\Services\CharacterSheet\CharacterSheetBuilder;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use App\Services\CharacterSheet\CharacterStateService;
use App\Services\CharacterSheet\SkillBonusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Fiche personnage côté MJ.
 *
 * Volontairement pensée pour le jeu en direct : chaque action est un POST/PUT
 * ciblé qui renvoie sur la fiche, de sorte que le MJ n'a jamais à traverser
 * une succession d'écrans CRUD pendant une partie.
 */
class CharacterController extends Controller
{
    public function show(Character $character, CharacterSheetPresenter $presenter, CharacterStateService $states): View
    {
        $character->load(CharacterSheetPresenter::RELATIONS);

        return view('gm.characters.show', [
            'character' => $character,
            'sheet' => $presenter->forGameMaster($character),
            'maps' => GameMap::query()->orderBy('sort_order')->get(),
            'masteryCatalog' => MasteryDefinition::query()->active()->availableTo($character)
                ->whereNotIn('id', $character->masteries->pluck('mastery_definition_id'))
                ->orderBy('name')->get(),
            'abilityCatalog' => AbilityDefinition::query()->active()->availableTo($character)
                ->whereNotIn('id', $character->abilities->pluck('ability_definition_id'))
                ->orderBy('name')->get(),
            'statePresets' => $states->presets(),
            'masteryRanks' => config('jdr.character.mastery_ranks'),
            'affinityLevels' => config('jdr.character.affinity_levels'),
        ]);
    }

    public function update(UpdateCharacterRequest $request, Character $character, CharacterManagementService $service): RedirectResponse
    {
        $service->updateCharacter($character, $request->payload());

        return back()->with('success', 'Fiche personnage mise à jour.');
    }

    /** Recrée les lignes de fiche manquantes après enrichissement du catalogue. */
    public function synchronise(Character $character, CharacterSheetBuilder $builder): RedirectResponse
    {
        $builder->initialize($character);

        return back()->with('success', 'Fiche synchronisée avec le catalogue de la campagne.');
    }

    // ── Caractéristiques ──────────────────────────────────────────────────

    /** Le MJ pose directement la valeur : aucune progression automatique. */
    public function updateAttribute(
        UpdateAttributeRequest $request,
        Character $character,
        CharacterAttribute $attribute,
        AttributeService $service,
    ): JsonResponse|RedirectResponse {
        $this->ensureOwnership($character, $attribute);
        $payload = $service->update($attribute, $request->validated());

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('success', 'Caractéristique mise à jour.');
    }

    // ── Révélation ────────────────────────────────────────────────────────

    /** Ouvre (ou referme) une donnée précise de la fiche au joueur. */
    public function reveal(
        RevealSheetEntryRequest $request,
        Character $character,
        CharacterRevealService $reveal,
    ): RedirectResponse {
        $entry = $reveal->resolve($character, $request->string('type')->value(), $request->integer('id'));
        $reveal->apply($entry, $request->enum('state', RevealState::class), $request->user());

        return back()->with('success', 'Visibilité mise à jour pour le joueur.');
    }

    // ── Compétences ───────────────────────────────────────────────────────

    public function updateSkill(
        UpdateCharacterSkillRequest $request,
        Character $character,
        CharacterSkill $skill,
        SkillBonusService $service,
    ): JsonResponse|RedirectResponse {
        $this->ensureOwnership($character, $skill);
        $payload = $service->update($skill, $request->validated(), forGameMaster: true);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('success', 'Compétence mise à jour.');
    }

    // ── Maîtrises ─────────────────────────────────────────────────────────

    public function storeMastery(CharacterMasteryRequest $request, Character $character, CharacterSheetBuilder $builder): RedirectResponse
    {
        $definition = MasteryDefinition::query()
            ->availableTo($character)
            ->findOrFail($request->integer('mastery_definition_id'));

        $mastery = $builder->attachMastery($character, $definition);
        $mastery->update($request->payload());

        return back()->with('success', 'Maîtrise ajoutée.');
    }

    public function updateMastery(CharacterMasteryRequest $request, Character $character, CharacterMastery $mastery): RedirectResponse
    {
        $this->ensureOwnership($character, $mastery);
        $mastery->update($request->payload());

        return back()->with('success', 'Maîtrise mise à jour.');
    }

    public function destroyMastery(Character $character, CharacterMastery $mastery): RedirectResponse
    {
        $this->ensureOwnership($character, $mastery);
        $mastery->delete();

        return back()->with('success', 'Maîtrise retirée.');
    }

    // ── Affinités magiques ────────────────────────────────────────────────

    public function updateAffinity(CharacterAffinityRequest $request, Character $character, CharacterAffinity $affinity): RedirectResponse
    {
        $this->ensureOwnership($character, $affinity);
        $affinity->update($request->validated());

        return back()->with('success', 'Affinité mise à jour.');
    }

    // ── Capacités ─────────────────────────────────────────────────────────

    public function storeAbility(CharacterAbilityRequest $request, Character $character, CharacterSheetBuilder $builder): RedirectResponse
    {
        $definition = AbilityDefinition::query()
            ->availableTo($character)
            ->findOrFail($request->integer('ability_definition_id'));

        $ability = $builder->attachAbility($character, $definition);
        $ability->update($request->payload());

        return back()->with('success', 'Capacité accordée.');
    }

    public function updateAbility(CharacterAbilityRequest $request, Character $character, CharacterAbility $ability): RedirectResponse
    {
        $this->ensureOwnership($character, $ability);
        $ability->update($request->payload());

        return back()->with('success', 'Capacité mise à jour.');
    }

    public function destroyAbility(Character $character, CharacterAbility $ability): RedirectResponse
    {
        $this->ensureOwnership($character, $ability);
        $ability->delete();

        return back()->with('success', 'Capacité retirée.');
    }

    // ── États ─────────────────────────────────────────────────────────────

    public function storeState(CharacterStateRequest $request, Character $character, CharacterStateService $states): RedirectResponse
    {
        $states->add($character, $request->payload());

        return back()->with('success', 'État appliqué.');
    }

    public function updateState(CharacterStateRequest $request, Character $character, CharacterState $state, CharacterStateService $states): RedirectResponse
    {
        $this->ensureOwnership($character, $state);
        $states->update($state, $request->payload());

        return back()->with('success', 'État mis à jour.');
    }

    public function destroyState(Character $character, CharacterState $state, CharacterStateService $states): RedirectResponse
    {
        $this->ensureOwnership($character, $state);
        $states->remove($state);

        return back()->with('success', 'État retiré.');
    }

    // ── Inventaire ────────────────────────────────────────────────────────

    public function storeItem(InventoryItemRequest $request, Character $character, CharacterManagementService $service): RedirectResponse
    {
        $service->addInventoryItem($character, $request->payload());

        return back()->with('success', 'Objet ajouté à l’inventaire.');
    }

    public function updateItem(InventoryItemRequest $request, Character $character, InventoryItem $item, CharacterManagementService $service): RedirectResponse
    {
        $this->ensureOwnership($character, $item);
        $service->updateInventoryItem($item, $request->payload());

        return back()->with('success', 'Objet mis à jour.');
    }

    public function destroyItem(Character $character, InventoryItem $item): RedirectResponse
    {
        $this->ensureOwnership($character, $item);
        $item->delete();

        return back()->with('success', 'Objet retiré de l’inventaire.');
    }

    /**
     * Garde-fou appliqué à chaque sous-ressource : empêche de manipuler la
     * ligne d'un personnage via l'URL d'un autre.
     */
    private function ensureOwnership(Character $character, mixed $record): void
    {
        abort_unless($record->character_id === $character->id, 404);
    }
}
