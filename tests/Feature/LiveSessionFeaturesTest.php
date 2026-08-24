<?php

use App\Enums\RevealState;
use App\Enums\UserRole;
use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->gm = User::query()->where('role', UserRole::GameMaster->value)->firstOrFail();
    $this->alice = createPlayer('alice-live@example.test', 'Alice');
    $this->bob = createPlayer('bob-live@example.test', 'Bob');
});

it('isole chaque conversation et pousse les nouveaux messages en temps réel', function () {
    Event::fake([ChatMessageSent::class]);

    $this->actingAs($this->alice)->get(route('chat.index'))->assertOk();
    $conversation = Conversation::query()
        ->where(fn ($query) => $query
            ->where('participant_one_id', $this->gm->id)
            ->where('participant_two_id', $this->alice->id))
        ->orWhere(fn ($query) => $query
            ->where('participant_one_id', $this->alice->id)
            ->where('participant_two_id', $this->gm->id))
        ->firstOrFail();

    $this->postJson(route('chat.messages.store', $conversation), ['body' => 'Le passage est libre.'])
        ->assertCreated()
        ->assertJsonPath('body', 'Le passage est libre.');

    expect(ChatMessage::query()->count())->toBe(1);
    Event::assertDispatched(ChatMessageSent::class, fn ($event) => $event->recipientId === $this->gm->id);

    $this->actingAs($this->bob)->get(route('chat.show', $conversation))->assertForbidden();
    $this->actingAs($this->gm)->getJson(route('chat.unread'))->assertJsonPath('count', 1);
    $this->get(route('chat.show', $conversation))->assertOk()->assertSee('Le passage est libre.');

    expect(ChatMessage::query()->firstOrFail()->read_at)->not->toBeNull();
});

it('cumule la base et les bonus distincts du MJ et du joueur', function () {
    $skill = $this->alice->character->skills()->with('definition.primaryAttribute', 'definition.secondaryAttribute')->firstOrFail();
    $skill->forceFill(['reveal_state' => RevealState::Revealed])->save();

    $this->actingAs($this->alice)->putJson(route('player.skills.bonus.update', $skill), [
        'player_bonus' => 2,
    ])->assertOk()->assertJsonPath('player_bonus', 2);

    $this->actingAs($this->gm)->put(route('gm.skills.update', [$this->alice->character, $skill]), [
        'bonus' => 5,
        'player_bonus' => 2,
        'reveal_state' => RevealState::Revealed->value,
        'gm_notes' => null,
    ])->assertSessionHasNoErrors();

    $row = app(CharacterSheetPresenter::class)
        ->forPlayer($this->alice->character->fresh()->load(CharacterSheetPresenter::RELATIONS))['skills']
        ->flatten(1)->firstWhere('id', $skill->id);

    expect($row['gm_bonus'])->toBe(5)
        ->and($row['player_bonus'])->toBe(2)
        ->and($row['value'])->toBe(min(100, $row['base_value'] + 7));

    $this->actingAs($this->bob)->putJson(route('player.skills.bonus.update', $skill), [
        'player_bonus' => 40,
    ])->assertNotFound();
});

it('synchronise vie et mana sans dépasser leurs maximums', function () {
    $character = $this->alice->character;
    $character->forceFill(['max_health' => 12, 'mana_max' => 8])->save();

    $this->actingAs($this->alice)->putJson(route('characters.resources.update', $character), [
        'resource' => 'health',
        'value' => 9,
    ])->assertOk()->assertJsonPath('health', 9);

    $this->actingAs($this->gm)->putJson(route('characters.resources.update', $character), [
        'resource' => 'mana_current',
        'value' => 99,
    ])->assertOk()->assertJsonPath('mana', 8);

    $this->actingAs($this->bob)->putJson(route('characters.resources.update', $character), [
        'resource' => 'health',
        'value' => 1,
    ])->assertForbidden();
});

it('laisse le MJ envoyer le portrait du personnage et ouvre la fiche complète aux compagnons', function () {
    Storage::fake('public');

    $this->actingAs($this->gm)->post(route('gm.characters.portrait.update', $this->alice->character), [
        'portrait' => UploadedFile::fake()->image('alice.png', 400, 400),
    ])->assertSessionHasNoErrors();

    expect($this->alice->character->fresh()->portrait_path)->toContain('/storage/portraits/');

    $this->actingAs($this->bob)->get(route('player.allies.show', $this->alice->character))
        ->assertOk()
        ->assertSee($this->alice->character->displayName())
        ->assertSee('Caractéristiques')
        ->assertSee('Compétences');
});
