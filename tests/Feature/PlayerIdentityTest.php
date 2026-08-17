<?php

/*
|--------------------------------------------------------------------------
| Identité, portrait et fiches des autres joueurs
|--------------------------------------------------------------------------
|
| Le joueur décrit son personnage lui-même, mais ne doit pas pouvoir se
| renforcer par ce biais. Et la fiche d'un compagnon montre ses chiffres,
| jamais son sac ni ses notes.
|
*/

use App\Enums\RevealState;
use App\Models\SkillDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->player = createPlayer('moi@example.test', 'Kael');
    $this->character = $this->player->character;
    $this->ally = createPlayer('compagnon@example.test', 'Lira');
});

it('laisse le joueur réécrire son identité et son histoire', function () {
    $this->actingAs($this->player)->put(route('player.identity.update'), [
        'first_name' => 'Kaelin',
        'last_name' => 'Vantriss',
        'nickname' => 'Le muet',
        'background' => 'Enfant de fermiers',
        'biography' => 'Il parle peu, observe beaucoup.',
        'traits' => 'Gaucher.',
    ])->assertSessionHasNoErrors();

    $character = $this->character->fresh();

    expect($character->first_name)->toBe('Kaelin')
        ->and($character->biography)->toBe('Il parle peu, observe beaucoup.')
        // Le nom affiché suit prénom + nom, sans quoi le reste du site décroche.
        ->and($character->name)->toBe('Kaelin Vantriss');
});

it('ne laisse pas le joueur se renforcer via l’écran d’identité', function () {
    $before = $this->character->health;

    $this->actingAs($this->player)->put(route('player.identity.update'), [
        'first_name' => 'Kael',
        'health' => 9999,
        'gold' => 9999,
        'house_id' => 1,
    ])->assertSessionHasNoErrors();

    $character = $this->character->fresh();

    expect($character->health)->toBe($before)
        ->and($character->gold)->not->toBe(9999);
});

it('accepte un portrait envoyé par le joueur et refuse un fichier douteux', function () {
    Storage::fake('public');

    $this->actingAs($this->player)->post(route('player.portrait.update'), [
        'portrait' => UploadedFile::fake()->image('moi.png', 300, 300),
    ])->assertSessionHasNoErrors();

    expect($this->character->fresh()->portrait_path)->toContain('/storage/portraits/');
    expect(Storage::disk('public')->files('portraits'))->toHaveCount(1);

    $this->actingAs($this->player)->post(route('player.portrait.update'), [
        'portrait' => UploadedFile::fake()->create('charge.php', 8, 'application/x-php'),
    ])->assertSessionHasErrors('portrait');
});

it('remplace le portrait sans laisser de fichier orphelin', function () {
    Storage::fake('public');

    $this->actingAs($this->player)->post(route('player.portrait.update'), [
        'portrait' => UploadedFile::fake()->image('un.png'),
    ]);
    $this->actingAs($this->player)->post(route('player.portrait.update'), [
        'portrait' => UploadedFile::fake()->image('deux.png'),
    ]);

    expect(Storage::disk('public')->files('portraits'))->toHaveCount(1);
});

it('montre les chiffres des autres joueurs mais ni leur sac ni leurs notes', function () {
    $this->ally->character->update(['biography' => 'Une enfance au bord du fleuve.']);
    $this->ally->character->inventoryItems()->create([
        'name' => 'Amulette secrète', 'category' => 'Bijoux',
        'quantity' => 1, 'is_visible_to_player' => true,
    ]);

    $response = $this->actingAs($this->player)->get(route('player.character'))->assertOk();

    $response->assertSee('Autres joueurs')
        ->assertSee('Lira')
        // L'inventaire d'un compagnon ne le regarde pas.
        ->assertDontSee('Amulette secrète');

    $allies = $response->viewData('allies');

    expect($allies)->toHaveCount(1)
        ->and($allies[0]['identity']['name'])->toContain('Lira')
        ->and($allies[0])->not->toHaveKeys(['inventory']);
});

it('ne transmet pas les compétences cachées d’un autre joueur', function () {
    $definitionId = SkillDefinition::query()->where('code', 'detection-mana')->value('id');

    $this->ally->character->skills()
        ->where('skill_definition_id', $definitionId)
        ->update(['reveal_state' => RevealState::Hidden, 'gm_notes' => 'Un don qu’elle ignore.']);

    $response = $this->actingAs($this->player)->get(route('player.character'))->assertOk();

    $response->assertDontSee('Un don qu’elle ignore.');

    $codes = collect($response->viewData('allies')[0]['skills'])->flatten(1)->pluck('code');

    expect($codes)->not->toContain('detection-mana');
});

it('n’inclut jamais le joueur lui-même dans la liste des autres', function () {
    $allies = $this->actingAs($this->player)->get(route('player.character'))->viewData('allies');

    expect(collect($allies)->pluck('id'))->not->toContain($this->character->id);
});
