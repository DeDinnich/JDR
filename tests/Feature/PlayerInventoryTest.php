<?php

/*
|--------------------------------------------------------------------------
| Inventaire tenu par le joueur
|--------------------------------------------------------------------------
|
| Le joueur gère son sac lui-même, mais ne doit jamais pouvoir atteindre un
| objet qu'il ignore transporter, ni le sac d'un autre.
|
*/

use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->player = createPlayer('sac@example.test', 'Kael');
    $this->character = $this->player->character;
    $this->other = createPlayer('autre@example.test', 'Lira');
});

it('permet au joueur d’ajouter, modifier puis retirer un objet', function () {
    $this->actingAs($this->player)->post(route('player.inventory.store'), [
        'name' => 'Corde de chanvre',
        'category' => 'Outils',
        'quantity' => 1,
    ])->assertSessionHasNoErrors();

    $item = $this->character->inventoryItems()->firstOrFail();

    expect($item->name)->toBe('Corde de chanvre')
        // Un objet créé par le joueur lui est forcément visible.
        ->and($item->is_visible_to_player)->toBeTrue();

    $this->actingAs($this->player)->put(route('player.inventory.update', $item), [
        'name' => 'Corde solide',
        'category' => 'Outils',
        'quantity' => 2,
        'equipped' => '1',
    ])->assertSessionHasNoErrors();

    expect($item->fresh()->name)->toBe('Corde solide')
        ->and($item->fresh()->quantity)->toBe(2)
        ->and($item->fresh()->equipped)->toBeTrue();

    $this->actingAs($this->player)->delete(route('player.inventory.destroy', $item))
        ->assertSessionHasNoErrors();

    expect(InventoryItem::query()->whereKey($item->id)->exists())->toBeFalse();
});

it('empêche le joueur de toucher un objet qu’il ignore transporter', function () {
    $hidden = $this->character->inventoryItems()->create([
        'name' => 'Lettre cousue', 'category' => 'Secrets',
        'quantity' => 1, 'is_visible_to_player' => false,
    ]);

    $this->actingAs($this->player)->put(route('player.inventory.update', $hidden), [
        'name' => 'Découvert', 'category' => 'Secrets', 'quantity' => 1,
    ])->assertNotFound();

    $this->actingAs($this->player)->delete(route('player.inventory.destroy', $hidden))
        ->assertNotFound();

    expect($hidden->fresh()->name)->toBe('Lettre cousue');
});

it('empêche le joueur de toucher le sac d’un autre', function () {
    $foreign = $this->other->character->inventoryItems()->create([
        'name' => 'Amulette', 'category' => 'Bijoux',
        'quantity' => 1, 'is_visible_to_player' => true,
    ]);

    $this->actingAs($this->player)->delete(route('player.inventory.destroy', $foreign))
        ->assertNotFound();

    expect($foreign->fresh())->not->toBeNull();
});

it('laisse le joueur ajuster sa bourse', function () {
    $this->actingAs($this->player)->put(route('player.resources.update'), [
        'health' => $this->character->health,
        'max_health' => $this->character->max_health,
        'mana_current' => $this->character->mana_current,
        'mana_max' => $this->character->mana_max,
        'gold' => 42,
    ])->assertSessionHasNoErrors();

    expect($this->character->fresh()->gold)->toBe(42);
});
