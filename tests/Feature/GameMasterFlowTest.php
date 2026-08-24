<?php

use App\Enums\UserRole;
use App\Events\SecretMessageDeleted;
use App\Events\SecretMessageSent;
use App\Models\Npc;
use App\Models\SecretMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->gameMaster = User::query()->where('role', UserRole::GameMaster->value)->firstOrFail();
    $this->recipient = createPlayer('recipient@example.test', 'Aelys');
    $this->otherPlayer = createPlayer('other@example.test', 'Brom');
});

test('the game master sends a private realtime message to one player', function () {
    Event::fake([SecretMessageSent::class]);

    $this->actingAs($this->gameMaster)->post(route('gm.messages.store'), [
        'recipient_id' => $this->recipient->id,
        'body' => 'Tu entends un murmure derrière la porte.',
    ])->assertSessionHasNoErrors();

    $message = SecretMessage::query()->latest('id')->firstOrFail();
    expect($message->recipient_id)->toBe($this->recipient->id)
        ->and($this->otherPlayer->receivedMessages()->whereKey($message->id)->exists())->toBeFalse();
    Event::assertDispatched(SecretMessageSent::class, fn ($event) => $event->message->is($message));
});

test('only the recipient can acknowledge a private message', function () {
    Event::fake();
    $message = SecretMessage::create([
        'sender_id' => $this->gameMaster->id,
        'recipient_id' => $this->recipient->id,
        'body' => 'Message privé',
        'priority' => 'important',
    ]);

    $this->actingAs($this->otherPlayer)->postJson(route('messages.read', $message))->assertForbidden();
    $this->actingAs($this->recipient)->postJson(route('messages.read', $message))->assertOk();

    expect($message->fresh()->read_at)->not->toBeNull();
});

test('the sender and recipient can delete a private message for both sides', function () {
    Event::fake([SecretMessageDeleted::class]);
    $message = SecretMessage::create([
        'sender_id' => $this->gameMaster->id,
        'recipient_id' => $this->recipient->id,
        'body' => 'Message à corriger',
    ]);

    $this->actingAs($this->otherPlayer)
        ->deleteJson(route('messages.destroy', $message))
        ->assertForbidden();

    $this->actingAs($this->recipient)
        ->deleteJson(route('messages.destroy', $message))
        ->assertOk()
        ->assertJsonPath('deleted', true);

    $this->assertDatabaseMissing('secret_messages', ['id' => $message->id]);
    Event::assertDispatched(SecretMessageDeleted::class, fn ($event) => $event->messageId === $message->id);

    $sentMessage = SecretMessage::create([
        'sender_id' => $this->gameMaster->id,
        'recipient_id' => $this->recipient->id,
        'body' => 'Second essai',
    ]);

    $this->actingAs($this->gameMaster)
        ->deleteJson(route('messages.destroy', $sentMessage))
        ->assertOk();

    $this->assertDatabaseMissing('secret_messages', ['id' => $sentMessage->id]);
});

test('a npc can be revealed to the whole table', function () {
    $npc = Npc::create(['name' => 'Le Chantre Aveugle', 'role' => 'Oracle']);

    $this->actingAs($this->gameMaster)->post(route('gm.npcs.reveal', $npc), ['scope' => 'all'])
        ->assertSessionHasNoErrors();

    expect($npc->discoveredBy()->count())->toBe(2);
});

it('donne au MJ son propre journal, isolé de celui des joueurs', function () {
    $this->actingAs($this->gameMaster)->post(route('gm.notes.store'), [
        'title' => 'Préparer l’embuscade',
        'content' => 'Trois gardes sur le pont nord.',
    ])->assertSessionHasNoErrors();

    $note = $this->gameMaster->notes()->firstOrFail();

    expect($note->title)->toBe('Préparer l’embuscade');

    $this->actingAs($this->gameMaster)->get(route('gm.notes.index'))
        ->assertOk()
        ->assertSee('Préparer l’embuscade');

    // Le joueur ne voit pas les notes du MJ, et n'atteint pas sa route.
    $this->actingAs($this->recipient)->get(route('player.notes.index'))
        ->assertOk()
        ->assertDontSee('Préparer l’embuscade');

    $this->actingAs($this->recipient)->get(route('gm.notes.index'))->assertForbidden();
});

it('empêche le MJ de modifier la note d’un joueur', function () {
    $note = $this->recipient->notes()->create([
        'title' => 'Ma note',
        'content' => 'Privé.',
    ]);

    $this->actingAs($this->gameMaster)
        ->put(route('gm.notes.update', $note), ['title' => 'Volée', 'content' => 'x'])
        ->assertForbidden();

    expect($note->fresh()->title)->toBe('Ma note');
});
