<?php

use App\Enums\UserRole;
use App\Events\MonsterRevealed;
use App\Models\Monster;
use App\Models\User;
use Database\Seeders\HouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(HouseSeeder::class);

    $this->gm = User::factory()->create([
        'role' => UserRole::GameMaster,
        'email' => 'mj@bestiaire.test',
    ]);
    $this->alice = playerWithOrigin('alice@bestiaire.test', 'Alice');
    $this->bob = playerWithOrigin('bob@bestiaire.test', 'Bob');
    $this->monster = Monster::create([
        'name' => 'Hydre cendrée',
        'type' => 'Aberration',
        'size' => 'Immense',
        'description' => 'Une masse écailleuse aux multiples cous.',
        'game_master_notes' => 'SECRET_MJ_COMPORTEMENT',
        'health_current' => 180,
        'health_max' => 220,
        'mana_current' => 30,
        'mana_max' => 50,
        'strength' => 18,
        'damage_dice' => '2d8+3',
        'weakness' => 'SECRET_MJ_FAIBLESSE_FROID',
    ]);
    $this->monster->abilities()->create([
        'name' => 'Souffle de braise',
        'description' => 'SECRET_MJ_CAPACITE',
        'dice_formula' => '3d6+2',
    ]);
});

it('sépare strictement les espaces MJ et joueur du bestiaire', function () {
    actingAs($this->alice)->get(route('gm.bestiary.index'))->assertForbidden();
    actingAs($this->alice)->get(route('gm.bestiary.import.show'))->assertForbidden();

    actingAs($this->gm)->get(route('gm.bestiary.index'))
        ->assertOk()
        ->assertSee('Hydre cendrée')
        ->assertSee('Importer en JSON');

    actingAs($this->alice)->get(route('player.bestiary.index'))
        ->assertOk()
        ->assertDontSee('Hydre cendrée');
});

it('optimise le portrait du monstre au format webp', function () {
    Storage::fake('public');

    actingAs($this->gm)->post(route('gm.bestiary.store'), [
        'name' => 'Manticore rouge',
        'portrait' => UploadedFile::fake()->image('manticore.png', 1200, 800),
    ])->assertRedirect();

    $monster = Monster::where('name', 'Manticore rouge')->firstOrFail();

    expect($monster->portrait_path)->toEndWith('.webp');
    Storage::disk('public')->assertExists('portraits/monsters/'.basename($monster->portrait_path));
});

it('révèle une créature au seul joueur sélectionné en temps réel', function () {
    Event::fake([MonsterRevealed::class]);

    actingAs($this->gm)->post(route('gm.bestiary.reveal', $this->monster), [
        'user_ids' => [$this->alice->id],
    ])->assertSessionHasNoErrors();

    Event::assertDispatched(
        MonsterRevealed::class,
        fn (MonsterRevealed $event) => $event->userId === $this->alice->id,
    );
    Event::assertDispatchedTimes(MonsterRevealed::class, 1);

    actingAs($this->alice)->get(route('player.bestiary.show', $this->monster))
        ->assertOk()
        ->assertSee('Hydre cendrée')
        ->assertDontSee('SECRET_MJ_COMPORTEMENT')
        ->assertDontSee('SECRET_MJ_FAIBLESSE_FROID')
        ->assertDontSee('SECRET_MJ_CAPACITE')
        ->assertDontSee('220');

    actingAs($this->bob)->get(route('player.bestiary.show', $this->monster))->assertNotFound();
});

it('isole les déductions de bestiaire de chaque joueur', function () {
    $this->monster->discoveredBy()->attach([
        $this->alice->id => ['discovered_at' => now()],
        $this->bob->id => ['discovered_at' => now()],
    ]);

    actingAs($this->alice)->put(route('player.bestiary.update', $this->monster), [
        'known_health' => 'Environ 200 points de vie',
        'known_mana' => 'Une réserve limitée',
        'known_abilities' => 'Elle crache des braises.',
        'known_damage' => 'Trois dés environ',
        'known_weakness' => 'Le froid semble la ralentir.',
        'personal_notes' => 'Ne jamais rester groupés.',
    ])->assertSessionHasNoErrors();

    actingAs($this->alice)->get(route('player.bestiary.show', $this->monster))
        ->assertOk()
        ->assertSee('Ne jamais rester groupés.');

    actingAs($this->bob)->get(route('player.bestiary.show', $this->monster))
        ->assertOk()
        ->assertDontSee('Ne jamais rester groupés.');

    expect($this->monster->fresh()->weakness)->toBe('SECRET_MJ_FAIBLESSE_FROID');
});

it('interdit la modification des déductions avant la révélation', function () {
    actingAs($this->alice)->put(route('player.bestiary.update', $this->monster), [
        'personal_notes' => 'Tentative interdite',
    ])->assertForbidden();

    expect($this->monster->discoveredBy()->whereKey($this->alice->id)->exists())->toBeFalse();
});

it('lance les dés côté serveur et protège les capacités imbriquées', function () {
    $ability = $this->monster->abilities()->firstOrFail();
    $otherMonster = Monster::create(['name' => 'Goule']);

    $response = actingAs($this->gm)
        ->postJson(route('gm.bestiary.abilities.roll', [$this->monster, $ability]))
        ->assertOk()
        ->assertJsonPath('formula', '3d6+2');

    expect($response->json('rolls'))->toHaveCount(3)
        ->and($response->json('total'))->toBeGreaterThanOrEqual(5)
        ->toBeLessThanOrEqual(20);

    actingAs($this->gm)
        ->postJson(route('gm.bestiary.abilities.roll', [$otherMonster, $ability]))
        ->assertNotFound();

    actingAs($this->alice)
        ->postJson(route('gm.bestiary.damage.roll', $this->monster))
        ->assertForbidden();
});

it('analyse puis importe un lot JSON sans écraser les doublons', function () {
    $payload = json_encode(['monsters' => [
        ['name' => 'Hydre cendrée', 'health' => ['max' => 999]],
        [
            'name' => 'Basilic de verre',
            'type' => 'Bête magique',
            'health' => ['current' => 45, 'max' => 45],
            'damage_dice' => '2d10+1',
            'abilities' => [[
                'name' => 'Regard pétrifiant',
                'dice_formula' => '1d20+4',
            ]],
        ],
    ]], JSON_THROW_ON_ERROR);

    actingAs($this->gm)->post(route('gm.bestiary.import.analyse'), ['json' => $payload])
        ->assertOk()
        ->assertSee('Basilic de verre')
        ->assertSee('Hydre cendrée');

    actingAs($this->gm)->post(route('gm.bestiary.import.store'), ['json' => $payload])
        ->assertRedirect(route('gm.bestiary.index'));

    expect(Monster::where('name', 'Hydre cendrée')->count())->toBe(1)
        ->and(Monster::where('name', 'Basilic de verre')->firstOrFail()->abilities()->count())->toBe(1);
});

it('refuse proprement un JSON invalide et exporte le schéma complet au MJ', function () {
    actingAs($this->gm)->post(route('gm.bestiary.import.store'), [
        'json' => '{"monsters":[{"name":"Cassé",,}]}',
    ])->assertOk()->assertSee('JSON invalide');

    expect(Monster::where('name', 'Cassé')->exists())->toBeFalse();

    $export = actingAs($this->gm)
        ->get(route('gm.bestiary.export.one', $this->monster))
        ->assertOk();

    expect($export->json('monsters.0.weakness'))->toBe('SECRET_MJ_FAIBLESSE_FROID')
        ->and($export->json('monsters.0.abilities.0.name'))->toBe('Souffle de braise');
});

it('ajoute les déductions du bestiaire aux extractions sans données secrètes MJ', function () {
    $this->monster->discoveredBy()->attach($this->alice->id, [
        'discovered_at' => now(),
        'known_weakness' => 'Le froid est peut-être efficace.',
        'personal_notes' => 'Créature rencontrée dans les ruines.',
    ]);

    $response = actingAs($this->gm)->post(route('gm.session-extractions.store'), [
        'user_ids' => [$this->alice->id],
    ])->assertOk()->assertDownload();

    $serialized = $response->streamedContent();

    expect($serialized)->toContain('Hydre cendrée')
        ->toContain('Le froid est peut-être efficace.')
        ->toContain('Créature rencontrée dans les ruines.')
        ->not->toContain('SECRET_MJ_FAIBLESSE_FROID')
        ->not->toContain('SECRET_MJ_CAPACITE')
        ->not->toContain('SECRET_MJ_COMPORTEMENT');
});
