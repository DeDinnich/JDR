<?php

/*
|--------------------------------------------------------------------------
| Cartes quadrillées
|--------------------------------------------------------------------------
|
| Le point sensible : une case non révélée ne doit pas seulement être masquée
| à l'écran, sa tuile ne doit pas être servie. Un joueur qui devine l'URL d'une
| case fermée doit repartir les mains vides.
|
*/

use App\Enums\UserRole;
use App\Models\GameMap;
use App\Models\MapCellReveal;
use App\Models\MapPoint;
use App\Models\User;
use App\Services\World\MapTileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/** Génère une vraie image PNG : le service décode réellement le fichier. */
function fakeMapImage(string $name = 'carte.png'): UploadedFile
{
    $image = imagecreatetruecolor(400, 300);
    imagefill($image, 0, 0, imagecolorallocate($image, 40, 80, 120));

    ob_start();
    imagepng($image);
    $binary = ob_get_clean();
    imagedestroy($image);

    $path = tempnam(sys_get_temp_dir(), 'map').'.png';
    file_put_contents($path, $binary);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

beforeEach(function () {
    Storage::fake('local');

    $this->gm = User::factory()->create(['role' => UserRole::GameMaster, 'email' => 'mj@carte.test']);
    $this->player = playerWithOrigin('joueur@carte.test');
    $this->other = playerWithOrigin('autre@carte.test');
});

it('découpe une carte importée et la laisse entièrement dans le noir', function () {
    actingAs($this->gm)->post(route('gm.maps.store'), [
        'title' => 'Vallée de Sylverac',
        'image' => fakeMapImage(),
        'grid_columns' => 4,
        'grid_rows' => 3,
    ])->assertSessionHasNoErrors();

    $map = GameMap::query()->firstOrFail();

    expect($map->grid_columns)->toBe(4)
        ->and($map->image_width)->toBe(400)
        ->and($map->image_height)->toBe(300)
        // 4 × 3 tuiles produites…
        ->and(Storage::disk('local')->files("maps/{$map->id}/tiles"))->toHaveCount(12)
        // …et aucune case ouverte.
        ->and($map->cellReveals()->count())->toBe(0);
});

it('refuse un fichier qui n’est pas une image', function () {
    actingAs($this->gm)->post(route('gm.maps.store'), [
        'title' => 'Piège',
        'image' => UploadedFile::fake()->create('charge.php', 12, 'application/x-php'),
        'grid_columns' => 2,
        'grid_rows' => 2,
    ])->assertSessionHasErrors('image');

    expect(GameMap::query()->count())->toBe(0);
});

it('ne sert pas la tuile d’une case fermée au joueur', function () {
    $map = createSlicedMap($this->gm);
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);

    // La carte est donnée, mais aucune case n'est ouverte.
    actingAs($this->player)->get(route('maps.tile', [$map, 0, 0]))->assertNotFound();

    MapCellReveal::create(['map_id' => $map->id, 'column' => 0, 'row' => 0]);

    actingAs($this->player)->get(route('maps.tile', [$map, 0, 0]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/webp');

    // La case voisine reste fermée.
    actingAs($this->player)->get(route('maps.tile', [$map, 0, 1]))->assertNotFound();
});

it('ne sert aucune tuile d’une carte que le joueur n’a pas', function () {
    $map = createSlicedMap($this->gm);
    MapCellReveal::create(['map_id' => $map->id, 'column' => 0, 'row' => 0]);

    actingAs($this->other)->get(route('maps.tile', [$map, 0, 0]))->assertNotFound();
});

it('laisse le MJ voir toutes les tuiles, même fermées', function () {
    $map = createSlicedMap($this->gm);

    actingAs($this->gm)->get(route('maps.tile', [$map, 1, 1]))->assertOk();
});

it('ouvre et referme une case depuis la grille du MJ', function () {
    $map = createSlicedMap($this->gm);

    actingAs($this->gm)->postJson(route('gm.maps.cells.toggle', $map), ['column' => 2, 'row' => 1])
        ->assertOk()->assertJson(['revealed' => true]);

    expect($map->cellReveals()->count())->toBe(1);

    actingAs($this->gm)->postJson(route('gm.maps.cells.toggle', $map), ['column' => 2, 'row' => 1])
        ->assertOk()->assertJson(['revealed' => false]);

    expect($map->cellReveals()->count())->toBe(0);
});

it('interdit au joueur d’ouvrir une case lui-même', function () {
    $map = createSlicedMap($this->gm);
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);

    actingAs($this->player)->postJson(route('gm.maps.cells.toggle', $map), ['column' => 0, 'row' => 0])
        ->assertForbidden();

    expect($map->cellReveals()->count())->toBe(0);
});

it('garde les repères d’un joueur invisibles des autres joueurs', function () {
    $map = createSlicedMap($this->gm);

    foreach ([$this->player, $this->other] as $player) {
        $map->discoveredBy()->attach($player->id, ['discovered_at' => now()]);
    }

    actingAs($this->player)->postJson(route('maps.points.store', $map), [
        'label' => 'Cache sous la souche',
        'x_position' => 40, 'y_position' => 60,
    ])->assertCreated();

    actingAs($this->player)->get(route('player.world.show', $map))
        ->assertOk()->assertSee('Cache sous la souche');

    actingAs($this->other)->get(route('player.world.show', $map))
        ->assertOk()->assertDontSee('Cache sous la souche');
});

it('n’envoie au joueur que les repères MJ explicitement ouverts', function () {
    $map = createSlicedMap($this->gm);
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);

    MapPoint::create([
        'map_id' => $map->id, 'user_id' => $this->gm->id, 'label' => 'Embuscade prévue',
        'x_position' => 10, 'y_position' => 10, 'is_visible_to_players' => false,
    ]);
    MapPoint::create([
        'map_id' => $map->id, 'user_id' => $this->gm->id, 'label' => 'Le vieux pont',
        'x_position' => 20, 'y_position' => 20, 'is_visible_to_players' => true,
    ]);

    actingAs($this->player)->get(route('player.world.show', $map))
        ->assertOk()
        ->assertSee('Le vieux pont')
        ->assertDontSee('Embuscade prévue');
});

it('empêche un joueur de publier un repère à toute la table', function () {
    $map = createSlicedMap($this->gm);
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);

    actingAs($this->player)->postJson(route('maps.points.store', $map), [
        'label' => 'Tentative', 'x_position' => 5, 'y_position' => 5,
        'is_visible_to_players' => true,
    ])->assertCreated();

    expect(MapPoint::query()->firstOrFail()->is_visible_to_players)->toBeFalse();
});

it('empêche de supprimer le repère d’un autre', function () {
    $map = createSlicedMap($this->gm);
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);

    $point = MapPoint::create([
        'map_id' => $map->id, 'user_id' => $this->other->id, 'label' => 'Le sien',
        'x_position' => 1, 'y_position' => 1,
    ]);

    actingAs($this->player)->deleteJson(route('maps.points.destroy', [$map, $point]))
        ->assertForbidden();

    expect(MapPoint::query()->whereKey($point->id)->exists())->toBeTrue();
});

it('laisse un joueur supprimer son propre repère', function () {
    $map = createSlicedMap($this->gm);
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);
    $point = $map->points()->create([
        'user_id' => $this->player->id,
        'label' => 'Passage secret',
        'x_position' => 25,
        'y_position' => 60,
    ]);

    actingAs($this->player)->deleteJson(route('maps.points.destroy', [$map, $point]))
        ->assertOk()->assertJson(['deleted' => true]);

    expect($point->fresh())->toBeNull();
});

/** Crée une carte réellement découpée, comme le ferait l'import. */
function createSlicedMap(User $gm): GameMap
{
    $map = GameMap::create([
        'title' => 'Sylverac',
        'slug' => 'sylverac',
        'grid_columns' => 4,
        'grid_rows' => 3,
        'is_active' => true,
    ]);

    $map->update(app(MapTileService::class)->slice($map, fakeMapImage(), 4, 3));

    return $map->refresh();
}

it('montre au MJ toutes les tuiles, y compris celles des cases fermées', function () {
    $map = createSlicedMap($this->gm);

    // La grille MJ demande une image par case pour qu'il voie ce qu'il ouvre.
    actingAs($this->gm)->get(route('gm.maps.grid', $map))
        ->assertOk()
        ->assertSee('tuiles/0/0', escape: false)
        ->assertSee('tuiles/2/3', escape: false);
});

it('n’envoie au joueur aucune image de case fermée', function () {
    $map = createSlicedMap($this->gm);
    $map->discoveredBy()->attach($this->player->id, ['discovered_at' => now()]);
    MapCellReveal::create(['map_id' => $map->id, 'column' => 0, 'row' => 0]);

    $response = actingAs($this->player)->get(route('player.world.show', $map))->assertOk();

    // Une seule case ouverte : une seule image de tuile dans toute la page.
    expect(substr_count($response->getContent(), 'tuiles/'))->toBe(1);
});

it('laisse le MJ donner puis retirer une carte à un joueur', function () {
    $map = createSlicedMap($this->gm);

    actingAs($this->gm)->put(route('gm.maps.access', $map), ['user_ids' => [$this->player->id]])
        ->assertSessionHasNoErrors();

    expect($map->discoveredBy()->whereKey($this->player->id)->exists())->toBeTrue();
    actingAs($this->player)->get(route('player.world.show', $map))->assertOk();

    // Case décochée : la carte lui est retirée.
    actingAs($this->gm)->put(route('gm.maps.access', $map), ['user_ids' => []])
        ->assertSessionHasNoErrors();

    expect($map->discoveredBy()->whereKey($this->player->id)->exists())->toBeFalse();
    actingAs($this->player)->get(route('player.world.show', $map))->assertNotFound();
});

it('interdit à un joueur de s’attribuer une carte', function () {
    $map = createSlicedMap($this->gm);

    actingAs($this->player)->put(route('gm.maps.access', $map), ['user_ids' => [$this->player->id]])
        ->assertForbidden();

    expect($map->discoveredBy()->count())->toBe(0);
});
