<?php

/*
|--------------------------------------------------------------------------
| Édition de la fiche par le MJ
|--------------------------------------------------------------------------
|
| Parcourt les actions que le MJ déclenche en pleine partie, afin de garantir
| qu'aucune d'elles ne casse silencieusement.
|
*/

use App\Enums\RevealState;
use App\Enums\UserRole;
use App\Models\AbilityDefinition;
use App\Models\MagicSchool;
use App\Models\MasteryDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->gameMaster = User::query()->where('role', UserRole::GameMaster->value)->firstOrFail();
    $this->player = createPlayer('enfant@example.test', 'Kael');
    $this->character = $this->player->character;
    $this->actingAs($this->gameMaster);
});

test('le MJ met à jour l’identité et les ressources d’un enfant sans lui imposer de classe', function () {
    $this->put(route('gm.characters.update', $this->character), [
        'first_name' => 'Kael',
        'last_name' => 'Vantriss',
        'age_years' => 6,
        'ancestry' => 'Humain',
        'origin' => 'Hameau de Bruyère-Basse',
        'health' => 9,
        'max_health' => 12,
        'mana_current' => 4,
        'mana_max' => null,
        'armor' => 0,
        'gold' => 3,
        'level' => 1,
        'experience' => 0,
        'status' => 'En forme',
    ])->assertSessionHasNoErrors();

    $character = $this->character->fresh();

    expect($character->displayName())->toBe('Kael Vantriss')
        ->and($character->name)->toBe('Kael Vantriss')
        ->and($character->age_years)->toBe(6)
        // Aucune classe n'est imposée : le champ reste vide.
        ->and($character->archetype)->toBeNull()
        // Mana maximum laissé vide : la formule reprend la main.
        ->and($character->mana_max)->toBeNull();
});

test('le MJ pose directement la valeur d’une caractéristique', function () {
    $attribute = $this->character->attributes()->firstOrFail();

    $this->put(route('gm.attributes.update', [$this->character, $attribute]), [
        'value' => 9,
        'modifier' => 1,
    ])->assertSessionHasNoErrors();

    $attribute->refresh();

    expect($attribute->value)->toBe(9)
        ->and($attribute->modifier)->toBe(1)
        ->and($attribute->effectiveValue())->toBe(10);
});

test('le MJ accorde une maîtrise puis la fait progresser', function () {
    $definition = MasteryDefinition::query()->where('code', 'magie-eau')->firstOrFail();

    $this->post(route('gm.masteries.store', $this->character), [
        'mastery_definition_id' => $definition->id,
        'rank_index' => 0,
        'progress' => 35,
        'predisposition' => 2,
        'reveal_state' => RevealState::Hidden->value,
    ])->assertSessionHasNoErrors();

    $mastery = $this->character->masteries()->firstOrFail();

    expect($mastery->rankLabel())->toBe('Novice')
        ->and($mastery->progress)->toBe(35);

    $this->put(route('gm.masteries.update', [$this->character, $mastery]), [
        'rank_index' => 2,
        'progress' => 0,
        'predisposition' => 2,
        'reveal_state' => RevealState::Revealed->value,
    ])->assertSessionHasNoErrors();

    expect($mastery->fresh()->rankLabel())->toBe('Avancé');

    $this->delete(route('gm.masteries.destroy', [$this->character, $mastery]))->assertSessionHasNoErrors();

    expect($this->character->masteries()->count())->toBe(0);
});

test('le MJ accorde une capacité et peut la garder secrète', function () {
    $definition = AbilityDefinition::query()->where('code', 'creation-eau')->firstOrFail();

    $this->post(route('gm.abilities.store', $this->character), [
        'ability_definition_id' => $definition->id,
        'unlocked' => '1',
        'reveal_state' => RevealState::Hidden->value,
    ])->assertSessionHasNoErrors();

    $ability = $this->character->abilities()->firstOrFail();

    expect($ability->unlocked)->toBeTrue()
        ->and($ability->reveal_state)->toBe(RevealState::Hidden);

    // Acquise mais inconnue : elle ne doit pas apparaître chez le joueur.
    $this->actingAs($this->player)->get(route('player.character'))
        ->assertOk()->assertDontSee('Création d’eau', escape: false);
});

test('le MJ règle une affinité magique et la garde cachée', function () {
    $affinity = $this->character->affinities()
        ->where('magic_school_id', MagicSchool::query()->where('code', 'eau')->value('id'))
        ->firstOrFail();

    $this->put(route('gm.affinities.update', [$this->character, $affinity]), [
        'affinity_level' => 4,
        'reveal_state' => RevealState::Hidden->value,
        'gm_notes' => 'Se manifestera près du ruisseau.',
    ])->assertSessionHasNoErrors();

    expect($affinity->fresh()->levelLabel())->toBe('Excellente');

    $this->actingAs($this->player)->get(route('player.character'))
        ->assertOk()->assertDontSee('ruisseau');
});

test('le MJ pose un état avec des modificateurs puis le retire', function () {
    $this->post(route('gm.states.store', $this->character), [
        'name' => 'Fatigué',
        'icon' => '☾',
        'description' => 'A veillé pendant l’orage.',
        'duration_label' => 'Jusqu’au prochain repos',
        'visible_to_player' => '1',
        'is_active' => '1',
        'modifiers' => ['end' => -1, 'dex' => -1, 'for' => 0],
    ])->assertSessionHasNoErrors();

    $state = $this->character->states()->firstOrFail();

    // Les modificateurs nuls sont écartés à l'enregistrement.
    expect($state->modifiers)->toBe(['end' => -1, 'dex' => -1])
        ->and($state->modifierSummary())->toBe('END -1 · DEX -1');

    $this->delete(route('gm.states.destroy', [$this->character, $state]))->assertSessionHasNoErrors();

    expect($this->character->states()->count())->toBe(0);
});

test('le MJ ajoute un bonus personnel sur une compétence', function () {
    $skill = $this->character->skills()->firstOrFail();

    $this->put(route('gm.skills.update', [$this->character, $skill]), [
        'bonus' => 2,
        'reveal_state' => RevealState::Revealed->value,
        'gm_notes' => 'Bricole sans arrêt avec les outils de son père.',
    ])->assertSessionHasNoErrors();

    expect($skill->fresh()->bonus)->toBe(2);
});

test('le MJ peut cacher une compétence au joueur', function () {
    $skill = $this->character->skills()->firstOrFail();

    $this->post(route('gm.reveal.store', $this->character), [
        'type' => 'skill',
        'id' => $skill->id,
        'state' => RevealState::Hidden->value,
    ])->assertSessionHasNoErrors();

    expect($skill->fresh()->reveal_state)->toBe(RevealState::Hidden);
});

test('le MJ glisse un objet caché dans l’inventaire d’un joueur', function () {
    $this->post(route('gm.inventory.store', $this->character), [
        'name' => 'Lettre cousue dans la doublure',
        'category' => 'Secrets',
        'quantity' => 1,
        'weight' => 0.01,
    ])->assertSessionHasNoErrors();

    $item = $this->character->inventoryItems()->firstOrFail();

    // La case n'a pas été cochée : l'objet reste invisible pour le joueur.
    expect($item->name)->toBe('Lettre cousue dans la doublure')
        ->and($item->is_visible_to_player)->toBeFalse();
});

test('la synchronisation rattache les compétences ajoutées au catalogue après la naissance', function () {
    $this->character->skills()->limit(3)->delete();
    $before = $this->character->skills()->count();

    $this->post(route('gm.characters.synchronise', $this->character))->assertSessionHasNoErrors();

    expect($this->character->skills()->count())->toBe($before + 3);
});
