<?php

/*
|--------------------------------------------------------------------------
| Base de PNJ : espace MJ, révélation, glossaire et import JSON
|--------------------------------------------------------------------------
|
| Vérifie la frontière serveur entre ce que sait le MJ et ce que sait CHAQUE
| joueur : deux joueurs peuvent connaître le même PNJ à des degrés différents,
| et aucun secret MJ ne doit jamais franchir cette frontière.
|
*/

use App\Enums\UserRole;
use App\Events\NpcRevealed;
use App\Models\Npc;
use App\Models\User;
use App\Services\Campaign\NpcPresenter;
use Database\Seeders\CampaignNpcSeeder;
use Database\Seeders\HouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed([HouseSeeder::class, CampaignNpcSeeder::class]);

    $this->gm = User::factory()->create(['role' => UserRole::GameMaster, 'email' => 'mj@demo.test']);
    $this->alice = playerWithOrigin('alice@demo.test', 'Alice');
    $this->bob = playerWithOrigin('bob@demo.test', 'Bob');

    $this->cassian = Npc::where('name', 'Cassian Vaelmont')->firstOrFail();
});

// ── Permissions ──────────────────────────────────────────────────────────

it('interdit à un joueur tout accès à l’espace PNJ du MJ', function () {
    actingAs($this->alice)->get(route('gm.npcs.index'))->assertForbidden();
    actingAs($this->alice)->get(route('gm.npcs.detail', $this->cassian))->assertForbidden();
    actingAs($this->alice)->get(route('gm.npcs.import.show'))->assertForbidden();
    actingAs($this->alice)->get(route('gm.npcs.export'))->assertForbidden();
    actingAs($this->alice)->post(route('gm.npcs.detail.reveal', $this->cassian), [
        'user_ids' => [$this->alice->id],
    ])->assertForbidden();
});

it('n’expose jamais un secret MJ dans la fiche joueur', function () {
    // Cassian est connu d'Alice, secrets compris côté MJ.
    $this->cassian->discoveredBy()->attach($this->alice->id, ['discovered_at' => now()]);

    actingAs($this->alice)->get(route('player.glossary.show', $this->cassian))
        ->assertOk()
        ->assertDontSee('correspondance secrète')
        ->assertDontSee('Rania');

    // La fiche PNJ n'affiche plus les secrets (le MJ les dit à voix haute),
    // mais ils restent à sa disposition dans l'export JSON.
    actingAs($this->gm)->get(route('gm.npcs.detail', $this->cassian))
        ->assertOk()
        ->assertDontSee('Correspondance avec Rania');

    expect(actingAs($this->gm)->get(route('gm.npcs.export.one', $this->cassian))->json('npcs.0.secrets.0.title'))
        ->toBe('Correspondance avec Rania');
});

// ── Glossaire ────────────────────────────────────────────────────────────

it('n’affiche pas dans le glossaire un PNJ non révélé', function () {
    actingAs($this->alice)->get(route('player.glossary.index'))
        ->assertOk()
        ->assertDontSee('Cassian Vaelmont');

    // Et la fiche directe se comporte comme si le PNJ n'existait pas.
    actingAs($this->alice)->get(route('player.glossary.show', $this->cassian))
        ->assertNotFound();
});

it('fait apparaître le PNJ dans le glossaire une fois révélé', function () {
    actingAs($this->gm)->post(route('gm.npcs.detail.reveal', $this->cassian), [
        'user_ids' => [$this->alice->id],
    ])->assertSessionHasNoErrors();

    actingAs($this->alice)->get(route('player.glossary.index'))
        ->assertOk()
        ->assertSee('Cassian Vaelmont');

    // Bob n'était pas visé : pour lui, rien n'a changé.
    actingAs($this->bob)->get(route('player.glossary.index'))
        ->assertOk()
        ->assertDontSee('Cassian Vaelmont');
});

it('diffuse la révélation en temps réel au seul joueur concerné', function () {
    Event::fake([NpcRevealed::class]);

    actingAs($this->gm)->post(route('gm.npcs.detail.reveal', $this->cassian), [
        'user_ids' => [$this->alice->id],
    ]);

    Event::assertDispatched(NpcRevealed::class, fn (NpcRevealed $event) => $event->userId === $this->alice->id);
    Event::assertDispatchedTimes(NpcRevealed::class, 1);
});

// ── Informations révélables ──────────────────────────────────────────────

it('garde inaccessible une information non révélée', function () {
    $this->cassian->discoveredBy()->attach($this->alice->id, ['discovered_at' => now()]);
    $information = $this->cassian->informations()->where('title', 'Fonction')->firstOrFail();

    $payload = app(NpcPresenter::class)->forPlayer($this->cassian, $this->alice);

    expect(collect($payload['informations'])->pluck('title'))->not->toContain($information->title);
});

it('rend visible une information une fois révélée, joueur par joueur', function () {
    $information = $this->cassian->informations()->where('title', 'Fonction')->firstOrFail();

    actingAs($this->gm)->post(
        route('gm.npcs.informations.reveal', [$this->cassian, $information]),
        ['user_ids' => [$this->alice->id]],
    )->assertSessionHasNoErrors();

    // La fiche du glossaire ne liste plus les informations, mais le
    // présentateur continue de les filtrer joueur par joueur : c'est ce
    // filtrage qui alimente la modale de révélation.
    $forAlice = app(NpcPresenter::class)->forPlayer($this->cassian, $this->alice);

    expect(collect($forAlice['informations'])->pluck('title'))->toContain('Fonction');

    actingAs($this->alice)->get(route('player.glossary.show', $this->cassian))->assertOk();

    // Bob découvre-t-il quoi que ce soit ? Non.
    actingAs($this->bob)->get(route('player.glossary.show', $this->cassian))
        ->assertNotFound();
});

it('refuse qu’une information soit atteinte via un autre PNJ', function () {
    $information = $this->cassian->informations()->firstOrFail();
    $other = Npc::where('name', 'Marta')->firstOrFail();

    actingAs($this->gm)->post(
        route('gm.npcs.informations.reveal', [$other, $information]),
        ['user_ids' => [$this->alice->id]],
    )->assertNotFound();
});

// ── Notes personnelles ───────────────────────────────────────────────────

it('isole les notes personnelles de chaque joueur', function () {
    foreach ([$this->alice, $this->bob] as $player) {
        $this->cassian->discoveredBy()->attach($player->id, ['discovered_at' => now()]);
    }

    actingAs($this->alice)->put(route('player.glossary.notes', $this->cassian), [
        'relationship' => 'mefiance',
        'personal_notes' => 'Il rôdait près de la bibliothèque après l’attaque.',
    ])->assertSessionHasNoErrors();

    actingAs($this->alice)->get(route('player.glossary.show', $this->cassian))
        ->assertOk()
        ->assertSee('Il rôdait près de la bibliothèque après l’attaque.');

    actingAs($this->bob)->get(route('player.glossary.show', $this->cassian))
        ->assertOk()
        ->assertDontSee('Il rôdait près de la bibliothèque après l’attaque.');

    // La fiche officielle du PNJ n'a pas bougé.
    expect($this->cassian->fresh()->description)->not->toContain('ment');
});

it('enregistre les notes envoyées par l’éditeur du glossaire en JSON', function () {
    $this->cassian->discoveredBy()->attach($this->alice->id, ['discovered_at' => now()]);

    actingAs($this->alice)->putJson(route('player.glossary.notes', $this->cassian), [
        'relationship' => 'allie',
        'personal_notes' => '<p><strong>Fiable</strong>, malgré son silence.</p>',
    ])->assertOk()->assertJsonStructure(['saved_at']);

    $pivot = $this->alice->discoveredNpcs()->whereKey($this->cassian->id)->firstOrFail()->pivot;

    expect($pivot->relationship)->toBe('allie')
        ->and($pivot->personal_notes)->toContain('<strong>Fiable</strong>');
});

// ── Import JSON ──────────────────────────────────────────────────────────

it('refuse proprement un JSON invalide, sans erreur 500', function () {
    actingAs($this->gm)->post(route('gm.npcs.import.store'), [
        'json' => '{"npcs": [ {"first_name": "Cassé",, } ]}',
    ])
        ->assertOk()
        ->assertSee('JSON invalide');

    expect(Npc::where('name', 'Cassé')->exists())->toBeFalse();
});

it('signale un champ obligatoire manquant en nommant le PNJ fautif', function () {
    actingAs($this->gm)->post(route('gm.npcs.import.store'), [
        'json' => json_encode(['npcs' => [
            ['first_name' => 'Valide'],
            ['last_name' => 'SansPrenom'],
        ]]),
    ])
        ->assertOk()
        ->assertSee('PNJ #2')
        ->assertSee('first_name est requis');

    // Rien n'est importé tant que le lot entier n'est pas valide.
    expect(Npc::where('name', 'Valide')->exists())->toBeFalse();
});

it('importe un lot valide avec ses secrets et ses informations', function () {
    actingAs($this->gm)->post(route('gm.npcs.import.store'), [
        'json' => json_encode(['npcs' => [[
            'first_name' => 'Doran',
            'last_name' => 'Merevoix',
            'age' => 44,
            'profession' => 'Armurier',
            'house' => 'valtheris',
            'status' => 'alive',
            'importance' => 'major',
            'tags' => ['artisan'],
            'public_description' => 'Un homme trapu aux mains brûlées.',
            'secrets' => [['title' => 'Dette', 'content' => 'Il doit de l’argent à la couronne.']],
            'revealable_information' => [['title' => 'Métier', 'content' => 'Il forge pour la garde.', 'category' => 'profession']],
        ]]]),
    ])->assertRedirect(route('gm.npcs.index'));

    $npc = Npc::where('name', 'Doran Merevoix')->firstOrFail();

    expect($npc->age)->toBe(44)
        ->and($npc->tags)->toBe(['artisan'])
        // Les synonymes anglais sont normalisés vers les clés internes.
        ->and($npc->status->value)->toBe('vivant')
        ->and($npc->importance->value)->toBe('majeur')
        ->and($npc->house->slug)->toBe('valtheris')
        ->and($npc->secrets()->count())->toBe(1)
        ->and($npc->informations()->count())->toBe(1)
        // Rien n'est révélé à l'import.
        ->and($npc->discoveredBy()->count())->toBe(0);
});

it('ignore un doublon au lieu de l’écraser', function () {
    $before = $this->cassian->description;

    actingAs($this->gm)->post(route('gm.npcs.import.store'), [
        'json' => json_encode(['npcs' => [
            ['first_name' => 'Cassian', 'last_name' => 'Vaelmont', 'public_description' => 'Écrasé !'],
            ['first_name' => 'Nouveau', 'last_name' => 'Venu'],
        ]]),
    ])->assertRedirect(route('gm.npcs.index'));

    expect($this->cassian->fresh()->description)->toBe($before)
        ->and(Npc::where('name', 'Cassian Vaelmont')->count())->toBe(1)
        ->and(Npc::where('name', 'Nouveau Venu')->exists())->toBeTrue();
});

// ── Export ───────────────────────────────────────────────────────────────

it('réserve l’export JSON au MJ et y inclut les secrets', function () {
    $response = actingAs($this->gm)->get(route('gm.npcs.export.one', $this->cassian))->assertOk();

    $payload = $response->json('npcs.0');

    expect($payload['first_name'])->toBe('Cassian')
        ->and($payload['secrets'][0]['title'])->toBe('Correspondance avec Rania');

    actingAs($this->alice)->get(route('gm.npcs.export.one', $this->cassian))->assertForbidden();
});
