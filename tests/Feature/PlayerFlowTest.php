<?php

use App\Models\GameMap;
use App\Models\Location;
use App\Models\Note;
use App\Models\Npc;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->player = createPlayer('owner@example.test', 'Aelys');
    $this->otherPlayer = createPlayer('other@example.test', 'Brom');
    $this->map = GameMap::create(['title' => 'Valdoren', 'slug' => 'valdoren']);
    $this->location = Location::create(['map_id' => $this->map->id, 'name' => 'Auberge', 'type' => 'Relais']);
    $this->npc = Npc::create(['location_id' => $this->location->id, 'name' => 'Mira', 'role' => 'Aubergiste', 'description' => 'Une femme attentive.']);
    $this->player->discoveredMaps()->attach($this->map);
    $this->player->discoveredLocations()->attach($this->location);
    $this->player->discoveredNpcs()->attach($this->npc, ['relationship' => 'neutre']);
});

test('a newly registered player can browse every companion section', function () {
    $this->actingAs($this->player)->get(route('player.character'))->assertOk()->assertSee($this->player->character->name);
    $this->get(route('player.character'))->assertOk();
    $this->get(route('player.inventory'))->assertOk()->assertSee('Inventaire');
    $this->get(route('player.world.index'))->assertOk()->assertSee($this->map->title);
    $this->get(route('player.world.show', $this->map))->assertOk();
    $this->get(route('player.notes.index'))->assertOk()->assertSee('Journal');
    $this->get(route('player.glossary.index'))->assertOk()->assertSee($this->npc->name);
    $this->get(route('player.npcs.show', $this->npc))->assertOk();
});

test('journal notes remain private to their owner', function () {
    $this->actingAs($this->player)->post(route('player.notes.store'), [
        'title' => 'Piste confidentielle',
        'content' => 'Ne pas faire confiance au passeur.',
        'pinned' => '1',
    ])->assertSessionHasNoErrors();

    $note = Note::query()->where('title', 'Piste confidentielle')->firstOrFail();
    expect($note->user_id)->toBe($this->player->id);

    $this->actingAs($this->otherPlayer)->put(route('player.notes.update', $note), [
        'title' => 'Note détournée',
        'content' => 'Altérée',
    ])->assertForbidden();
});

test('a player can maintain personal npc notes without changing global data', function () {
    $this->actingAs($this->player)->put(route('player.npcs.update', $this->npc), [
        'relationship' => 'mefiance',
        'personal_notes' => 'Son histoire change à chaque récit.',
    ])->assertSessionHasNoErrors();

    $pivot = $this->player->discoveredNpcs()->whereKey($this->npc->id)->firstOrFail()->pivot;
    expect($pivot->relationship)->toBe('mefiance')
        ->and($pivot->personal_notes)->toBe('Son histoire change à chaque récit.')
        ->and($this->npc->fresh()->description)->toBe('Une femme attentive.');
});
