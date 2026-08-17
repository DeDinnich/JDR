<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed());

test('an unauthenticated visitor is redirected to login', function () {
    $this->get('/')->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk()->assertDontSee('Comptes de démonstration');
    $this->get(route('register'))->assertOk()->assertSee('Rejoindre la campagne');
});

test('the seed creates only the game master from environment configuration', function () {
    $gameMaster = User::query()->sole();

    expect($gameMaster->email)->toBe(config('jdr.admin.email'))
        ->and($gameMaster->role)->toBe(UserRole::GameMaster)
        ->and($gameMaster->character)->toBeNull();
});

test('a player registration creates a complete minimal character and authenticates it', function () {
    $this->post(route('register.store'), [
        'character_name' => 'Aelys des Brumes',
        'email' => 'aelys@example.test',
        'password' => 'Player1234',
        'password_confirmation' => 'Player1234',
    ])->assertRedirect(route('player.character'))->assertSessionHasNoErrors();

    $player = User::query()->where('email', 'aelys@example.test')->firstOrFail();

    $this->assertAuthenticatedAs($player);
    expect($player->role)->toBe(UserRole::Player)
        ->and($player->character->name)->toBe('Aelys des Brumes')
        ->and($player->character->attributes)->toHaveCount(6);
});

test('players and game master have isolated workspaces', function () {
    $player = createPlayer();
    $gameMaster = User::query()->where('role', UserRole::GameMaster->value)->firstOrFail();

    $this->actingAs($player)->get(route('player.character'))->assertOk()->assertSee($player->character->name);
    $this->actingAs($player)->get(route('gm.dashboard'))->assertForbidden();

    $this->actingAs($gameMaster)->get(route('gm.dashboard'))->assertOk()->assertSee('Vue de la tablée');
    $this->actingAs($gameMaster)->get(route('player.character'))->assertForbidden();
});

test('game master and registered player credentials redirect to their own workspace', function () {
    $player = createPlayer();

    $this->post(route('login.store'), [
        'email' => config('jdr.admin.email'),
        'password' => config('jdr.admin.password'),
    ])->assertRedirect(route('gm.dashboard'));

    $this->post(route('logout'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $player->email, 'password' => 'Player1234'])
        ->assertRedirect(route('player.character'));
});
