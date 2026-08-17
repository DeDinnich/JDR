<?php

use App\Enums\UserRole;
use App\Models\House;
use App\Models\User;
use App\Services\PlayerRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Joueur prêt à jouer : inscrit ET installé dans une maison.
 *
 * L'origine est indispensable ici : le middleware EnsureOriginChosen renvoie
 * tout joueur sans maison vers l'écran de naissance, ce qui rendrait chaque
 * test de page inutilisable. Les tests du parcours de création, eux, partent
 * de bareUser() et posent l'origine eux-mêmes.
 */
function createPlayer(string $email = 'player@example.test', string $name = 'Aelys'): User
{
    $user = app(PlayerRegistrationService::class)->register([
        'character_name' => $name,
        'email' => $email,
        'password' => 'Player1234',
        'password_confirmation' => 'Player1234',
    ]);

    if ($user->character?->house_id === null) {
        // Première maison encore libre, quelle qu'elle soit : le test ne
        // dépend pas de laquelle, seulement du fait d'en avoir une.
        $house = House::query()->assignable()->whereDoesntHave('characters')->orderBy('sort_order')->first()
            ?? House::query()->assignable()->orderBy('sort_order')->first();

        if ($house !== null) {
            $user->character->forceFill(['house_id' => $house->getKey()])->save();
        }
    }

    return $user->refresh();
}

/**
 * Joueur minimal mais jouable : un compte, un personnage, une origine.
 *
 * Plus léger que createPlayer() — il ne monte pas la fiche complète — pour les
 * tests qui ne s'intéressent ni aux caractéristiques ni aux compétences, mais
 * qui ont malgré tout besoin d'un joueur que le middleware laisse passer.
 */
function playerWithOrigin(string $email, string $name = 'Joueur'): User
{
    $house = House::query()->assignable()->whereDoesntHave('characters')->orderBy('sort_order')->first()
        ?? House::create([
            'slug' => 'maison-'.Str::lower(Str::random(6)),
            'name' => 'Maison de test',
            'is_active' => true,
            'is_reserved' => false,
        ]);

    $user = User::factory()->create([
        'email' => $email,
        'name' => $name,
        'role' => UserRole::Player,
    ]);

    $user->character()->create([
        'name' => $name,
        'first_name' => $name,
        'house_id' => $house->getKey(),
        'health' => 10,
        'max_health' => 10,
    ]);

    return $user->refresh();
}
