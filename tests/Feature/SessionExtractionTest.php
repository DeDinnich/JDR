<?php

use App\Enums\RevealState;
use App\Enums\UserRole;
use App\Models\GameMap;
use App\Models\Location;
use App\Models\MapCellReveal;
use App\Models\MapPoint;
use App\Models\Npc;
use App\Models\SecretMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->gameMaster = User::query()->where('role', UserRole::GameMaster->value)->firstOrFail();
    $this->selectedPlayer = createPlayer('selected-export@example.test', 'Aelys Export');
    $this->otherPlayer = createPlayer('excluded-export@example.test', 'Brom Exclu');
});

it('exports a selected player snapshot with only their current knowledge', function () {
    $this->selectedPlayer->notes()->create([
        'title' => 'Piste de la crypte',
        'content' => 'Chercher sous le vieux chêne.',
        'pinned' => true,
    ]);

    $skills = $this->selectedPlayer->character->skills()->with('definition')->take(2)->get();
    $skills[0]->forceFill([
        'reveal_state' => RevealState::Revealed,
        'gm_notes' => 'NOTE_MJ_COMPETENCE_INTERDITE',
    ])->save();
    $skills[1]->forceFill([
        'reveal_state' => RevealState::Hidden,
        'gm_notes' => 'COMPETENCE_CACHEE_INTERDITE',
    ])->save();

    $this->selectedPlayer->character->inventoryItems()->create([
        'name' => 'Médaillon connu',
        'category' => 'Quête',
        'quantity' => 1,
        'is_visible_to_player' => true,
    ]);
    $this->selectedPlayer->character->inventoryItems()->create([
        'name' => 'LETTRE_CACHEE_INTERDITE',
        'category' => 'Secret',
        'quantity' => 1,
        'is_visible_to_player' => false,
    ]);

    $npc = Npc::create([
        'name' => 'Ysoria la Veilleuse',
        'game_master_notes' => 'SECRET_MJ_PNJ_INTERDIT',
    ]);
    $npc->discoveredBy()->attach($this->selectedPlayer->id, [
        'relationship' => 'alliée',
        'personal_notes' => 'Elle connaît le passage du nord.',
        'discovered_at' => now(),
    ]);
    $knownInformation = $npc->informations()->create([
        'title' => 'Signe reconnu',
        'content' => 'Elle porte la marque du corbeau.',
        'category' => 'identite',
    ]);
    $knownInformation->revealedTo()->attach($this->selectedPlayer->id, ['revealed_at' => now()]);
    $npc->informations()->create([
        'title' => 'INFORMATION_PNJ_CACHEE_INTERDITE',
        'content' => 'Ne doit jamais sortir.',
        'category' => 'secret',
    ]);

    $map = GameMap::create([
        'title' => 'Marais des murmures',
        'slug' => 'marais-export',
        'description' => 'Une route noyée dans la brume.',
        'is_active' => true,
    ]);
    $map->discoveredBy()->attach($this->selectedPlayer->id, ['discovered_at' => now()]);
    MapCellReveal::create(['map_id' => $map->id, 'column' => 2, 'row' => 3]);
    $location = Location::create([
        'map_id' => $map->id,
        'name' => 'Le vieux chêne',
        'type' => 'Repère',
        'description' => 'Un arbre fendu par la foudre.',
        'x_position' => 32,
        'y_position' => 47,
    ]);
    $location->discoveredBy()->attach($this->selectedPlayer->id, ['discovered_at' => now()]);
    MapPoint::create([
        'map_id' => $map->id,
        'user_id' => $this->selectedPlayer->id,
        'label' => 'Entrée supposée',
        'x_position' => 30,
        'y_position' => 50,
    ]);

    SecretMessage::create([
        'sender_id' => $this->gameMaster->id,
        'recipient_id' => $this->selectedPlayer->id,
        'body' => 'Le médaillon chauffe près du chêne.',
    ]);

    $response = $this->actingAs($this->gameMaster)
        ->post(route('gm.session-extractions.store'), [
            'user_ids' => [$this->selectedPlayer->id],
        ])
        ->assertOk()
        ->assertDownload();

    $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);
    $serialized = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    expect($payload['schema_version'])->toBe('1.0')
        ->and($payload['players'])->toHaveCount(1)
        ->and($payload['players'][0]['player']['name'])->toBe('Aelys Export')
        ->and($serialized)->toContain('Piste de la crypte')
        ->toContain('Elle connaît le passage du nord.')
        ->toContain('Elle porte la marque du corbeau.')
        ->toContain('Médaillon connu')
        ->toContain('Marais des murmures')
        ->toContain('Le vieux chêne')
        ->toContain('Entrée supposée')
        ->toContain('Le médaillon chauffe près du chêne.')
        ->not->toContain('Brom Exclu')
        ->not->toContain('NOTE_MJ_COMPETENCE_INTERDITE')
        ->not->toContain('COMPETENCE_CACHEE_INTERDITE')
        ->not->toContain('LETTRE_CACHEE_INTERDITE')
        ->not->toContain('SECRET_MJ_PNJ_INTERDIT')
        ->not->toContain('INFORMATION_PNJ_CACHEE_INTERDITE');
});

it('rejects empty selections and non-player accounts', function () {
    $this->actingAs($this->gameMaster)
        ->post(route('gm.session-extractions.store'), ['user_ids' => []])
        ->assertSessionHasErrors('user_ids');

    $this->actingAs($this->gameMaster)
        ->post(route('gm.session-extractions.store'), ['user_ids' => [$this->gameMaster->id]])
        ->assertSessionHasErrors('user_ids.0');

    $this->actingAs($this->selectedPlayer)
        ->post(route('gm.session-extractions.store'), ['user_ids' => [$this->selectedPlayer->id]])
        ->assertForbidden();
});
