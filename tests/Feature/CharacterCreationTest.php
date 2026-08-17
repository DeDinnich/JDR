<?php

/*
|--------------------------------------------------------------------------
| Naissance du personnage et attribution de l'origine
|--------------------------------------------------------------------------
|
| Vérifie le parcours de création (identité minimale puis tirage d'origine),
| l'exclusivité des trois grandes maisons, le cas du compte à origine réservée
| et — surtout — qu'aucun secret MJ ne franchit la frontière serveur/joueur.
|
*/

use App\Enums\UserRole;
use App\Events\HouseTaken;
use App\Models\House;
use App\Models\Npc;
use App\Models\User;
use App\Services\PlayerRegistrationService;
use Database\Seeders\AttributeDefinitionSeeder;
use Database\Seeders\CampaignNpcSeeder;
use Database\Seeders\HouseSeeder;
use Database\Seeders\MagicSchoolSeeder;
use Database\Seeders\SkillDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed([
        AttributeDefinitionSeeder::class,
        MagicSchoolSeeder::class,
        SkillDefinitionSeeder::class,
        HouseSeeder::class,
        CampaignNpcSeeder::class,
    ]);
});

/** Compte joueur sans aucun personnage : le point de départ du parcours. */
function bareUser(string $email = 'joueur@demo.test'): User
{
    return User::factory()->create(['email' => $email, 'role' => UserRole::Player]);
}

it('renvoie un joueur sans personnage vers le parcours de naissance', function () {
    actingAs(bareUser())
        ->get(route('player.character'))
        ->assertRedirect(route('player.creation.show'));
});

it('crée un nouveau-né sans classe ni statistiques choisies', function () {
    $user = bareUser();

    actingAs($user)->post(route('player.creation.store'), [
        'first_name' => 'Lior',
        'last_name' => 'Tessane',
    ])->assertRedirect(route('player.creation.show'));

    $character = $user->character()->firstOrFail();

    expect($character->age_years)->toBe(0)
        ->and($character->house_id)->toBeNull()
        // La fiche est montée d'office : six caractéristiques, toutes visibles.
        ->and($character->attributes()->count())->toBe(6);
});

it('interdit à un joueur de créer un second personnage', function () {
    $user = bareUser();
    $user->character()->create(['name' => 'Premier', 'health' => 6, 'max_health' => 6]);

    actingAs($user)
        ->post(route('player.creation.store'), ['first_name' => 'Doublon'])
        ->assertForbidden();
});

it('propose les trois grandes maisons à un joueur standard', function () {
    $user = bareUser();
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Lior']);

    $response = actingAs($user)->get(route('player.creation.show'));

    $response->assertOk()
        ->assertSee('Maison Valtheris')
        ->assertSee('Maison Aerendis')
        ->assertSee('Maison Vaelmont')
        // La famille Veyre est réservée : elle ne doit jamais apparaître.
        ->assertDontSee('Famille Veyre');
});

it('installe le joueur dans la maison qu’il choisit et présente les parents', function () {
    $user = bareUser();
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Lior']);

    actingAs($user)->post(route('player.creation.choose'), ['house' => 'aerendis'])
        ->assertRedirect(route('player.creation.origin'));

    $house = $user->character()->firstOrFail()->house;

    expect($house->slug)->toBe('aerendis')
        ->and($house->is_reserved)->toBeFalse();

    actingAs($user)->get(route('player.creation.origin'))->assertOk()->assertSee($house->name);
});

it('applique les caractéristiques de départ de la maison choisie', function () {
    $user = bareUser();
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Lior']);
    actingAs($user)->post(route('player.creation.choose'), ['house' => 'valtheris']);

    $expected = config('jdr.character.house_base_stats.valtheris');
    $values = $user->character()->firstOrFail()
        ->attributes()->with('definition')->get()
        ->mapWithKeys(fn ($attribute) => [$attribute->definition->code => $attribute->value]);

    expect($values['for'])->toBe($expected['for'])
        ->and($values['man'])->toBe($expected['man'])
        // Une maison d'érudits ne donne pas les mêmes bases.
        ->and($expected['for'])->not->toBe(config('jdr.character.house_base_stats.aerendis.for'));
});

it('refuse une maison déjà prise et laisse le joueur rejouer', function () {
    $first = bareUser('premier@demo.test');
    actingAs($first)->post(route('player.creation.store'), ['first_name' => 'Premier']);
    actingAs($first)->post(route('player.creation.choose'), ['house' => 'vaelmont']);

    $second = bareUser('second@demo.test');
    actingAs($second)->post(route('player.creation.store'), ['first_name' => 'Second']);
    actingAs($second)->post(route('player.creation.choose'), ['house' => 'vaelmont'])
        ->assertSessionHasErrors('house');

    expect($second->character()->firstOrFail()->house_id)->toBeNull();
});

it('refuse qu’un joueur réclame l’origine réservée', function () {
    $user = bareUser();
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Lior']);

    actingAs($user)->post(route('player.creation.choose'), ['house' => 'veyre'])
        ->assertSessionHasErrors('house');

    expect($user->character()->firstOrFail()->house_id)->toBeNull();
});

it('empêche de changer de maison une fois l’origine posée', function () {
    $user = bareUser();
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Lior']);
    actingAs($user)->post(route('player.creation.choose'), ['house' => 'aerendis']);

    actingAs($user)->post(route('player.creation.choose'), ['house' => 'valtheris'])
        ->assertRedirect(route('player.character'));

    expect($user->character()->firstOrFail()->house->slug)->toBe('aerendis');
});

it('ne montre jamais les grandes maisons au compte à origine réservée', function () {
    $user = bareUser(config('jdr.campaign.special_origin.email'));
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Jade']);

    // Son origine étant déjà posée, l'écran de choix ne s'ouvre même pas.
    actingAs($user)->get(route('player.creation.show'))
        ->assertRedirect(route('player.character'));
});

it('attribue l’origine Veyre au compte réservé avec un père inconnu', function () {
    $user = bareUser(config('jdr.campaign.special_origin.email'));
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Jade']);

    // L'origine est posée dès la création : aucun écran de choix pour ce compte.
    expect($user->character()->firstOrFail()->house->slug)->toBe('veyre');

    $response = actingAs($user)->get(route('player.creation.origin'));

    $response->assertOk()
        ->assertSee('Éléonora Veyre')
        // Le secret du roi ne doit apparaître nulle part dans la réponse.
        ->assertDontSee('Alaric')
        ->assertDontSee('roi d’Ashura', false);
});

it('ne révèle jamais au joueur Veyre que le roi est son père', function () {
    $user = bareUser(config('jdr.campaign.special_origin.email'));
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Jade']);

    $mother = Npc::where('name', 'Éléonora Veyre')->firstOrFail();

    // Le secret existe bien en base, côté MJ...
    expect($mother->secrets()->count())->toBe(1)
        ->and($mother->secrets()->first()->content)->toContain('roi');

    // ...mais aucune information révélée au joueur ne le contient.
    $revealed = $mother->informations()
        ->whereHas('revealedTo', fn ($query) => $query->whereKey($user->id))
        ->get();

    expect($revealed)->not->toBeEmpty();
    foreach ($revealed as $information) {
        expect($information->content)->not->toContain('roi')
            ->and($information->content)->not->toContain('Alaric');
    }

    expect($revealed->firstWhere('title', 'Ton père')?->content)->toBe('Inconnu.');
});

it('empêche deux joueurs de recevoir la même maison', function () {
    $slugs = [];

    foreach (['valtheris', 'aerendis', 'vaelmont'] as $index => $slug) {
        $user = bareUser("joueur{$index}@demo.test");
        actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Enfant']);
        actingAs($user)->post(route('player.creation.choose'), ['house' => $slug]);

        $slugs[] = $user->character()->firstOrFail()->house->slug;
    }

    expect($slugs)->toHaveCount(3)
        ->and(array_unique($slugs))->toHaveCount(3);
});

it('grise la maison choisie chez les autres joueurs, en direct', function () {
    Event::fake([HouseTaken::class]);

    $user = bareUser();
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Lior']);
    actingAs($user)->post(route('player.creation.choose'), ['house' => 'vaelmont']);

    Event::assertDispatched(HouseTaken::class, fn (HouseTaken $event) => $event->house->slug === 'vaelmont');
});

it('n’expose aucune description MJ des maisons au joueur', function () {
    $user = bareUser();
    actingAs($user)->post(route('player.creation.store'), ['first_name' => 'Lior']);

    $response = actingAs($user)->get(route('player.creation.show'));

    foreach (House::all() as $house) {
        if (filled($house->game_master_description)) {
            $response->assertDontSee($house->game_master_description);
        }
    }
});

it('impose le choix de la maison à un joueur inscrit par le formulaire public', function () {
    // L'inscription publique crée déjà le personnage : sans garde-fou, le
    // joueur atterrissait sur sa fiche sans jamais avoir choisi d'origine.
    $user = app(PlayerRegistrationService::class)->register([
        'character_name' => 'Nouveau',
        'email' => 'inscrit@demo.test',
        'password' => 'MotDePasse123',
    ]);

    expect($user->character->house_id)->toBeNull();

    // Aucune page joueur n'est atteignable tant que l'origine n'est pas posée.
    foreach (['player.character', 'player.inventory', 'player.notes.index', 'player.glossary.index'] as $route) {
        actingAs($user)->get(route($route))->assertRedirect(route('player.creation.show'));
    }

    actingAs($user)->get(route('player.creation.show'))
        ->assertOk()
        ->assertSee('Choisis ta famille');

    actingAs($user)->post(route('player.creation.choose'), ['house' => 'valtheris']);

    // Origine posée : le site se rouvre.
    actingAs($user)->get(route('player.character'))->assertOk();
});

it('n’impose pas le choix au compte réservé inscrit par le formulaire public', function () {
    $user = app(PlayerRegistrationService::class)->register([
        'character_name' => 'Jade',
        'email' => config('jdr.campaign.special_origin.email'),
        'password' => 'MotDePasse123',
    ]);

    expect($user->character->fresh()->house->slug)->toBe('veyre');

    actingAs($user)->get(route('player.character'))->assertOk();
});
