<?php

use App\Enums\UserRole;
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

test('a npc can be revealed to the whole table', function () {
    $npc = Npc::create(['name' => 'Le Chantre Aveugle', 'role' => 'Oracle']);

    $this->actingAs($this->gameMaster)->post(route('gm.npcs.reveal', $npc), ['scope' => 'all'])
        ->assertSessionHasNoErrors();

    expect($npc->discoveredBy()->count())->toBe(2);
});
