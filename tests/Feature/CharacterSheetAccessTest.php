<?php

/*
|--------------------------------------------------------------------------
| Contrôle d'accès aux routes de la fiche
|--------------------------------------------------------------------------
|
| Un joueur ne doit jamais pouvoir atteindre une route MJ, ni agir sur la
| fiche d'un autre joueur — y compris la sienne.
|
*/

use App\Enums\RevealState;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->gameMaster = User::query()->where('role', UserRole::GameMaster->value)->firstOrFail();
    $this->player = createPlayer('enfant@example.test', 'Kael');
    $this->otherPlayer = createPlayer('autre@example.test', 'Brom');
    $this->character = $this->player->character;
});

test('un joueur ne peut pas ouvrir la fiche MJ, pas même la sienne', function () {
    $this->actingAs($this->player)
        ->get(route('gm.characters.show', $this->character))
        ->assertForbidden();
});

test('un joueur ne peut pas modifier ses caractéristiques via la route MJ', function () {
    $attribute = $this->character->attributes()->firstOrFail();
    $attribute->update(['value' => 8]);

    $this->actingAs($this->player)
        ->put(route('gm.attributes.update', [$this->character, $attribute->id]), [
            'value' => 99,
            'modifier' => 0,
        ])
        ->assertForbidden();

    expect($attribute->fresh()->value)->toBe(8);
});

test('un joueur ne peut pas se révéler une compétence cachée à lui-même', function () {
    $skill = $this->character->skills()->firstOrFail();
    $skill->update(['reveal_state' => RevealState::Hidden]);

    $this->actingAs($this->player)
        ->post(route('gm.reveal.store', $this->character), [
            'type' => 'skill',
            'id' => $skill->id,
            'state' => RevealState::Revealed->value,
        ])
        ->assertForbidden();

    expect($skill->fresh()->reveal_state)->toBe(RevealState::Hidden);
});

test('un joueur ne peut pas modifier la fiche d’un autre joueur', function () {
    $this->actingAs($this->otherPlayer)
        ->put(route('gm.characters.update', $this->character), ['first_name' => 'Usurpé'])
        ->assertForbidden();

    expect($this->character->fresh()->first_name)->not->toBe('Usurpé');
});

test('un visiteur non authentifié est renvoyé vers la connexion', function () {
    $this->get(route('player.character'))->assertRedirect(route('login'));
    $this->get(route('gm.characters.show', $this->character))->assertRedirect(route('login'));
});

test('le MJ accède à la fiche complète et y retrouve les données internes', function () {
    $this->character->attributes()->firstOrFail()->update(['value' => 12]);
    $this->character->skills()->firstOrFail()->update([
        'reveal_state' => RevealState::Hidden,
        'gm_notes' => 'Aptitude encore ignorée de lui.',
    ]);

    $this->actingAs($this->gameMaster)
        ->get(route('gm.characters.show', $this->character))
        ->assertOk()
        ->assertSee('Caractéristiques')
        ->assertSee('12')
        ->assertSee('Aptitude encore ignorée de lui.');
});

test('les sous-ressources d’un personnage ne sont pas atteignables via un autre', function () {
    $foreignState = $this->otherPlayer->character->states()->create([
        'name' => 'Béni', 'visible_to_player' => true, 'is_active' => true,
    ]);

    $this->actingAs($this->gameMaster)
        ->delete(route('gm.states.destroy', [$this->character, $foreignState]))
        ->assertNotFound();

    expect($foreignState->fresh())->not->toBeNull();
});
