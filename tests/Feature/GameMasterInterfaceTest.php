<?php

use App\Enums\UserRole;
use App\Events\CharacterSheetUpdated;
use App\Models\GameMap;
use App\Models\MapCellReveal;
use App\Models\Npc;
use App\Models\User;
use App\Services\World\MapTileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->gameMaster = User::query()->where('role', UserRole::GameMaster->value)->firstOrFail();
    $this->player = createPlayer('interface@example.test', 'Aelis');
});

it('affiche et pilote vie et mana directement sur le dashboard MJ', function () {
    $response = $this->actingAs($this->gameMaster)->get(route('gm.dashboard'))->assertOk();

    $response->assertSee('data-character-resources="'.$this->player->character->id.'"', false)
        ->assertSee('data-resource="health"', false)
        ->assertSee('data-resource="mana_current"', false)
        ->assertDontSee('Sans zone');
});

it('remplace les commandes rapides par extraction de séance et suppression des messages', function () {
    $message = $this->gameMaster->receivedMessages()->create([
        'sender_id' => $this->gameMaster->id,
        'recipient_id' => $this->player->id,
        'body' => 'Message de test',
    ]);

    $this->actingAs($this->gameMaster)->get(route('gm.dashboard'))
        ->assertOk()
        ->assertSee('Extraction de séance')
        ->assertSee(route('gm.session-extractions.store'))
        ->assertSee(route('messages.destroy', $message))
        ->assertDontSee('Commandes rapides');
});

it('ouvre la fiche MJ sur les caractéristiques partagées et garde identité en dernier', function () {
    $html = $this->actingAs($this->gameMaster)
        ->get(route('gm.characters.show', $this->player->character))
        ->assertOk()
        ->assertSee('id="gmtab-stats" checked', false)
        ->assertSee('data-attribute-open', false)
        ->assertSee('data-skill-gm-input', false)
        ->getContent();

    expect(strpos($html, 'for="gmtab-stats"'))->toBeLessThan(strpos($html, 'for="gmtab-identity"'));
});

it('recalcule la fiche en AJAX sans diffuser les données privées du MJ', function () {
    Event::fake([CharacterSheetUpdated::class]);
    $attribute = $this->player->character->attributes()->firstOrFail();

    $this->actingAs($this->gameMaster)->putJson(route('gm.attributes.update', [
        $this->player->character,
        $attribute,
    ]), ['value' => 18, 'modifier' => 2])
        ->assertOk()
        ->assertJsonPath('attributes.0.value', 18);

    Event::assertDispatched(CharacterSheetUpdated::class, function (CharacterSheetUpdated $event): bool {
        $payload = $event->broadcastWith();

        return $payload['character_id'] === $this->player->character->id
            && ! str_contains(json_encode($payload, JSON_THROW_ON_ERROR), 'gm_notes');
    });
});

it('optimise les portraits PNJ en WebP carré', function () {
    Storage::fake('public');
    $npc = Npc::query()->firstOrFail();

    $this->actingAs($this->gameMaster)->post(route('gm.npcs.portrait.update', $npc), [
        'portrait' => UploadedFile::fake()->image('portrait.png', 1200, 700),
    ])->assertSessionHasNoErrors();

    $path = 'portraits/npcs/'.basename($npc->fresh()->portrait_path);
    Storage::disk('public')->assertExists($path);
    $size = getimagesizefromstring(Storage::disk('public')->get($path));

    expect($path)->toEndWith('.webp')
        ->and($size['mime'])->toBe('image/webp')
        ->and($size[0])->toBe(768)
        ->and($size[1])->toBe(768);
});

it('sert un aperçu complet au MJ et un aperçu avec brouillard au joueur', function () {
    Storage::fake('local');
    $map = GameMap::create([
        'title' => 'Bois brumeux', 'slug' => 'bois-brumeux', 'grid_columns' => 2, 'grid_rows' => 2,
        'is_active' => true, 'sort_order' => 1,
    ]);
    $map->update(app(MapTileService::class)->slice($map, interfaceMapImage(), 2, 2));
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);
    MapCellReveal::create(['map_id' => $map->id, 'column' => 0, 'row' => 0]);

    $gameMasterPreview = $this->actingAs($this->gameMaster)->get(route('maps.preview', $map))
        ->assertOk()->assertHeader('Content-Type', 'image/webp')->getContent();
    $playerPreview = $this->actingAs($this->player)->get(route('maps.preview', $map))
        ->assertOk()->assertHeader('Content-Type', 'image/webp')->getContent();

    expect($playerPreview)->not->toBe($gameMasterPreview);

    $other = createPlayer('sans-carte@example.test', 'Bryn');
    $this->actingAs($other)->get(route('maps.preview', $map))->assertNotFound();
});

it('rend les cartes entièrement cliquables sans anciens badges', function () {
    Storage::fake('local');
    $map = GameMap::create([
        'title' => 'Les Marches', 'slug' => 'les-marches', 'grid_columns' => 2, 'grid_rows' => 2,
        'is_active' => true, 'sort_order' => 1,
    ]);
    $map->update(app(MapTileService::class)->slice($map, interfaceMapImage(), 2, 2));
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);

    $this->actingAs($this->gameMaster)->get(route('gm.world.index'))
        ->assertOk()->assertSee(route('gm.maps.grid', $map))->assertDontSee('Ouvrir le quadrillage')->assertDontSee('2 × 2 cases');

    $this->actingAs($this->player)->get(route('player.world.index'))
        ->assertOk()->assertSee(route('maps.preview', $map))->assertDontSee('lieu(x) connu(s)');
});

function interfaceMapImage(): UploadedFile
{
    $image = imagecreatetruecolor(400, 240);
    imagefill($image, 0, 0, imagecolorallocate($image, 95, 125, 70));
    ob_start();
    imagepng($image);
    $contents = ob_get_clean();
    imagedestroy($image);
    $path = tempnam(sys_get_temp_dir(), 'interface-map').'.png';
    file_put_contents($path, $contents);

    return new UploadedFile($path, 'map.png', 'image/png', null, true);
}
