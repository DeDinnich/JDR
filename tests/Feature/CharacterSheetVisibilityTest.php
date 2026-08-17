<?php

/*
|--------------------------------------------------------------------------
| Confidentialité de la fiche personnage
|--------------------------------------------------------------------------
|
| Le point le plus sensible du module : une donnée non révélée ne doit jamais
| quitter le serveur à destination du joueur. Ces tests vérifient la réponse
| HTTP réelle, pas seulement le service — un joueur qui ouvre les outils de
| développement ne doit rien trouver de plus que ce que la page affiche.
|
| Depuis la simplification, les six caractéristiques principales sont toujours
| visibles : ce qui peut être caché, ce sont les compétences, maîtrises,
| affinités, capacités, états et objets d'inventaire.
|
*/

use App\Enums\RevealState;
use App\Enums\UserRole;
use App\Models\AttributeDefinition;
use App\Models\MagicSchool;
use App\Models\MasteryDefinition;
use App\Models\SkillDefinition;
use App\Models\User;
use App\Services\CharacterSheet\CharacterSheetBuilder;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->player = createPlayer('enfant@example.test', 'Kael');
    $this->character = $this->player->character;
    $this->gameMaster = User::query()->where('role', UserRole::GameMaster->value)->firstOrFail();

    $this->attribute = fn (string $code) => $this->character->attributes()
        ->where('attribute_definition_id', AttributeDefinition::query()->where('code', $code)->value('id'))
        ->firstOrFail();

    $this->sheetForPlayer = fn () => app(CharacterSheetPresenter::class)
        ->forPlayer($this->character->fresh()->load(CharacterSheetPresenter::RELATIONS));
});

test('un nouveau personnage naît avec une fiche complète et des caractéristiques visibles', function () {
    expect($this->character->attributes()->count())->toBe(6)
        ->and($this->character->skills()->count())->toBeGreaterThan(20)
        ->and($this->character->affinities()->count())->toBe(MagicSchool::query()->count())
        ->and($this->character->age_years)->toBe(0)
        ->and($this->character->archetype)->toBeNull();
});

test('les six caractéristiques sont toujours transmises au joueur avec leur valeur', function () {
    ($this->attribute)('man')->update(['value' => 17]);
    ($this->attribute)('int')->update(['value' => 13]);

    $sheet = ($this->sheetForPlayer)();

    expect($sheet['attributes'])->toHaveCount(6)
        ->and($sheet['attributes']->firstWhere('code', 'man')['value'])->toBe(17)
        ->and($sheet['attributes']->firstWhere('code', 'int')['value'])->toBe(13);

    $this->actingAs($this->player)->get(route('player.character'))
        ->assertOk()
        ->assertSee('MAN')
        ->assertSee('<span class="stat-value">17</span>', escape: false);
});

test('une compétence est un pourcentage calculé depuis les caractéristiques', function () {
    // Combat rapproché = FOR / DEX. Moyenne (12+14)/2 = 13, puis × 5 = 65 %.
    ($this->attribute)('for')->update(['value' => 12]);
    ($this->attribute)('dex')->update(['value' => 14]);

    $skill = ($this->sheetForPlayer)()['skills']->flatten(1)->firstWhere('code', 'combat-rapproche');

    expect($skill['base_value'])->toBe(65)
        ->and($skill['bonus'])->toBe(0)
        ->and($skill['value'])->toBe(65)
        ->and($skill['display'])->toBe('65 %');
});

test('une compétence ne dépasse jamais 100 % ni ne descend sous 0 %', function () {
    // Caractéristiques au plafond : la moyenne × 5 dépasserait 100.
    ($this->attribute)('for')->update(['value' => 30]);
    ($this->attribute)('dex')->update(['value' => 30]);

    $definitionId = SkillDefinition::query()->where('code', 'combat-rapproche')->value('id');
    $this->character->skills()->where('skill_definition_id', $definitionId)->update(['bonus' => 40]);

    $skill = ($this->sheetForPlayer)()['skills']->flatten(1)->firstWhere('code', 'combat-rapproche');

    expect($skill['value'])->toBe(100);

    // Et un malus massif ne rend pas la compétence négative.
    $this->character->skills()->where('skill_definition_id', $definitionId)->update(['bonus' => -999]);

    $skill = ($this->sheetForPlayer)()['skills']->flatten(1)->firstWhere('code', 'combat-rapproche');

    expect($skill['value'])->toBe(0);
});

test('un enfant de huit ans démarre à 5 % sur ses compétences', function () {
    foreach (['for', 'end', 'dex', 'int', 'cha', 'man'] as $code) {
        ($this->attribute)($code)->update(['value' => 1]);
    }

    $skill = ($this->sheetForPlayer)()['skills']->flatten(1)->firstWhere('code', 'combat-rapproche');

    expect($skill['value'])->toBe(5);
});

test('le bonus manuel du MJ modifie la valeur finale de la compétence', function () {
    ($this->attribute)('for')->update(['value' => 12]);
    ($this->attribute)('dex')->update(['value' => 14]);

    $definitionId = SkillDefinition::query()->where('code', 'combat-rapproche')->value('id');
    $this->character->skills()->where('skill_definition_id', $definitionId)->update(['bonus' => 2]);

    $skill = ($this->sheetForPlayer)()['skills']->flatten(1)->firstWhere('code', 'combat-rapproche');

    // 65 % de base, plus 2 points de pourcentage accordés par le MJ.
    expect($skill['base_value'])->toBe(65)
        ->and($skill['bonus'])->toBe(2)
        ->and($skill['value'])->toBe(67);
});

test('une compétence cachée par le MJ est absente de la réponse joueur', function () {
    $definitionId = SkillDefinition::query()->where('code', 'detection-mana')->value('id');

    $this->character->skills()
        ->where('skill_definition_id', $definitionId)
        ->update(['reveal_state' => RevealState::Hidden, 'gm_notes' => 'Il ignore encore ce don.']);

    $response = $this->actingAs($this->player)->get(route('player.character'));

    $response->assertOk()
        ->assertDontSee('Détection du mana')
        ->assertDontSee('Il ignore encore ce don');

    $codes = ($this->sheetForPlayer)()['skills']->flatten(1)->pluck('code');

    expect($codes)->not->toContain('detection-mana');
});

test('le MJ voit la compétence cachée et sa note', function () {
    $definitionId = SkillDefinition::query()->where('code', 'detection-mana')->value('id');

    $this->character->skills()
        ->where('skill_definition_id', $definitionId)
        ->update(['reveal_state' => RevealState::Hidden, 'gm_notes' => 'Il ignore encore ce don.']);

    $sheet = app(CharacterSheetPresenter::class)
        ->forGameMaster($this->character->fresh()->load(CharacterSheetPresenter::RELATIONS));

    $skill = $sheet['skills']->flatten(1)->firstWhere('code', 'detection-mana');

    expect($skill)->not->toBeNull()
        ->and($skill['reveal_state'])->toBe('hidden')
        ->and($skill['gm_notes'])->toBe('Il ignore encore ce don.');
});

test('un objet caché de l’inventaire n’est jamais transmis au joueur', function () {
    $this->character->inventoryItems()->createMany([
        ['name' => 'Couteau de poche', 'category' => 'Outils', 'quantity' => 1, 'is_visible_to_player' => true],
        ['name' => 'Lettre cousue', 'category' => 'Secrets', 'description' => 'Un pli scellé.', 'quantity' => 1, 'is_visible_to_player' => false],
    ]);

    $this->actingAs($this->player)->get(route('player.inventory'))
        ->assertOk()
        ->assertSee('Couteau de poche')
        ->assertDontSee('Lettre cousue')
        ->assertDontSee('Un pli scellé');

    $names = ($this->sheetForPlayer)()['inventory']->pluck('name');

    expect($names)->toContain('Couteau de poche')
        ->and($names)->not->toContain('Lettre cousue');
});

test('le MJ voit les objets cachés de l’inventaire', function () {
    $this->character->inventoryItems()->create([
        'name' => 'Lettre cousue', 'category' => 'Secrets',
        'quantity' => 1, 'is_visible_to_player' => false,
    ]);

    $this->actingAs($this->gameMaster)->get(route('gm.characters.show', $this->character))
        ->assertOk()
        ->assertSee('Lettre cousue');
});

test('les maîtrises, affinités et notes MJ cachées sont absentes de la réponse joueur', function () {
    $builder = app(CharacterSheetBuilder::class);
    $water = MasteryDefinition::query()->where('code', 'magie-eau')->firstOrFail();

    $builder->attachMastery($this->character, $water)->update([
        'rank_index' => 2,
        'reveal_state' => RevealState::Hidden,
        'gm_notes' => 'A fait trembler la surface du seau sans le toucher.',
    ]);

    $this->character->affinities()
        ->where('magic_school_id', MagicSchool::query()->where('code', 'eau')->value('id'))
        ->update([
            'affinity_level' => 4,
            'reveal_state' => RevealState::Hidden,
            'gm_notes' => 'Affinité exceptionnelle.',
        ]);

    $response = $this->actingAs($this->player)->get(route('player.character'));

    $response->assertOk()
        ->assertDontSee('Magie de l’eau', escape: false)
        ->assertDontSee('trembler la surface')
        ->assertDontSee('Affinité exceptionnelle')
        ->assertDontSee('Avancé');

    $sheet = ($this->sheetForPlayer)();

    expect($sheet['masteries'])->toBeEmpty()
        ->and($sheet['affinities'])->toBeEmpty();
});

test('un état invisible au joueur n’apparaît pas sur sa fiche', function () {
    $this->character->states()->createMany([
        ['name' => 'Fatigué', 'visible_to_player' => true, 'is_active' => true],
        ['name' => 'Maudit', 'description' => 'Une marque ancienne.', 'visible_to_player' => false, 'is_active' => true],
    ]);

    $this->actingAs($this->player)->get(route('player.character'))
        ->assertOk()
        ->assertSee('Fatigué')
        ->assertDontSee('Maudit')
        ->assertDontSee('marque ancienne');
});

test('le tableau de bord applique le même filtrage que la fiche', function () {
    ($this->attribute)('int')->update(['value' => 13]);

    $definitionId = SkillDefinition::query()->where('code', 'detection-mana')->value('id');
    $this->character->skills()->where('skill_definition_id', $definitionId)
        ->update(['reveal_state' => RevealState::Hidden]);

    $response = $this->actingAs($this->player)->get(route('player.character'))->assertOk();

    $response->assertSee('<span class="stat-value">13</span>', escape: false)
        ->assertDontSee('Détection du mana');

    $sheet = $response->viewData('sheet');

    expect($sheet['attributes']->firstWhere('code', 'int')['value'])->toBe(13)
        ->and($sheet['skills']->flatten(1)->pluck('code'))->not->toContain('detection-mana');
});
