<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CharacterResourceController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Gm\CharacterController as GmCharacterController;
use App\Http\Controllers\Gm\CharacterPortraitController as GmCharacterPortraitController;
use App\Http\Controllers\Gm\DashboardController as GmDashboardController;
use App\Http\Controllers\Gm\MapGridController;
use App\Http\Controllers\Gm\NpcController as GmNpcController;
use App\Http\Controllers\Gm\NpcImportController;
use App\Http\Controllers\Gm\NpcPortraitController;
use App\Http\Controllers\Gm\SecretMessageController as GmSecretMessageController;
use App\Http\Controllers\Gm\SessionExtractionController;
use App\Http\Controllers\Gm\WorldController as GmWorldController;
use App\Http\Controllers\MapPointController;
use App\Http\Controllers\MapPreviewController;
use App\Http\Controllers\MapTileController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\Player\AllyController;
use App\Http\Controllers\Player\CharacterController as PlayerCharacterController;
use App\Http\Controllers\Player\CharacterCreationController;
use App\Http\Controllers\Player\CharacterSkillController as PlayerCharacterSkillController;
use App\Http\Controllers\Player\GlossaryController;
use App\Http\Controllers\Player\IdentityController;
use App\Http\Controllers\Player\InventoryController as PlayerInventoryController;
use App\Http\Controllers\Player\NpcController;
use App\Http\Controllers\Player\WorldController as PlayerWorldController;
use App\Http\Controllers\SecretMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if (! $request->user()) {
        return redirect()->route('login');
    }

    return redirect()->route($request->user()->role === UserRole::GameMaster ? 'gm.dashboard' : 'player.character');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/connexion', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:8,1')->name('login.store');
    Route::get('/inscription', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/messages/non-lus', [SecretMessageController::class, 'unread'])->name('messages.unread');
    Route::post('/messages/{secretMessage}/lecture', [SecretMessageController::class, 'read'])->name('messages.read');
    Route::delete('/messages/{secretMessage}', [SecretMessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/non-lus/compteur', [ChatController::class, 'unread'])->name('chat.unread');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'store'])
        ->middleware('throttle:60,1')->name('chat.messages.store');
    Route::post('/chat/{conversation}/lecture', [ChatController::class, 'read'])->name('chat.read');
    Route::put('/personnages/{character}/ressources', [CharacterResourceController::class, 'update'])
        ->name('characters.resources.update');

    // Tuiles de carte : la seule voie d'accès aux images découpées. Le
    // contrôleur vérifie que la case est ouverte pour le demandeur.
    Route::get('/cartes/{map}/tuiles/{row}/{column}', MapTileController::class)
        ->whereNumber(['row', 'column'])->name('maps.tile');
    Route::get('/cartes/{map}/apercu', MapPreviewController::class)->name('maps.preview');
    Route::post('/cartes/{map}/reperes', [MapPointController::class, 'store'])->name('maps.points.store');
    Route::delete('/cartes/{map}/reperes/{point}', [MapPointController::class, 'destroy'])->name('maps.points.destroy');

    // `origin` force le choix de la maison : aucune page joueur n'est
    // atteignable tant que le personnage n'a pas d'origine.
    Route::prefix('joueur')->name('player.')->middleware(['role:player', 'origin'])->group(function () {
        // La fiche est la page d'accueil du joueur : la vue d'ensemble faisait
        // doublon avec elle et a été retirée.
        Route::get('/', PlayerCharacterController::class)->name('character');
        Route::get('/compagnons/{character}', [AllyController::class, 'show'])->name('allies.show');
        Route::put('/competences/{skill}/bonus', [PlayerCharacterSkillController::class, 'update'])
            ->name('skills.bonus.update');

        // Naissance du personnage : accessible tant que le joueur n'a pas
        // d'enfant complet (identité + origine).
        Route::get('/creation', [CharacterCreationController::class, 'show'])->name('creation.show');
        Route::post('/creation', [CharacterCreationController::class, 'store'])->name('creation.store');
        Route::post('/creation/origine', [CharacterCreationController::class, 'chooseOrigin'])->name('creation.choose');
        Route::get('/creation/origine', [CharacterCreationController::class, 'showOrigin'])->name('creation.origin');
        Route::get('/inventaire', [PlayerInventoryController::class, 'index'])->name('inventory');
        Route::post('/inventaire', [PlayerInventoryController::class, 'store'])->name('inventory.store');
        Route::put('/inventaire/{item}', [PlayerInventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventaire/{item}', [PlayerInventoryController::class, 'destroy'])->name('inventory.destroy');
        Route::put('/ressources', [PlayerInventoryController::class, 'updateResources'])->name('resources.update');

        // Identité et portrait : le joueur décrit son personnage lui-même.
        Route::put('/identite', [IdentityController::class, 'update'])->name('identity.update');
        Route::post('/portrait', [IdentityController::class, 'updatePortrait'])->name('portrait.update');
        Route::delete('/portrait', [IdentityController::class, 'destroyPortrait'])->name('portrait.destroy');
        Route::get('/monde', [PlayerWorldController::class, 'index'])->name('world.index');
        Route::get('/monde/{map}', [PlayerWorldController::class, 'show'])->name('world.show');
        Route::get('/journal', [NoteController::class, 'index'])->name('notes.index');
        Route::post('/journal', [NoteController::class, 'store'])->name('notes.store');
        Route::put('/journal/{note}', [NoteController::class, 'update'])->name('notes.update');
        Route::delete('/journal/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        Route::get('/personnages/{npc}', [NpcController::class, 'show'])->name('npcs.show');
        Route::put('/personnages/{npc}', [NpcController::class, 'update'])->name('npcs.update');

        // Glossaire : uniquement les PNJ révélés à CE joueur.
        Route::get('/glossaire', [GlossaryController::class, 'index'])->name('glossary.index');
        Route::get('/glossaire/{npc}', [GlossaryController::class, 'show'])->name('glossary.show');
        Route::put('/glossaire/{npc}/notes', [GlossaryController::class, 'updateNotes'])->name('glossary.notes');
    });

    Route::prefix('maitre-du-jeu')->name('gm.')->middleware('role:game_master')->group(function () {
        Route::get('/', GmDashboardController::class)->name('dashboard');
        // Fiche personnage : toutes les actions sont scopées au personnage pour
        // qu'aucune sous-ressource ne puisse être atteinte via un autre.
        Route::prefix('/personnages/{character}')->group(function () {
            Route::get('/', [GmCharacterController::class, 'show'])->name('characters.show');
            Route::put('/', [GmCharacterController::class, 'update'])->name('characters.update');
            Route::post('/synchroniser', [GmCharacterController::class, 'synchronise'])->name('characters.synchronise');
            Route::post('/portrait', [GmCharacterPortraitController::class, 'update'])->name('characters.portrait.update');
            Route::delete('/portrait', [GmCharacterPortraitController::class, 'destroy'])->name('characters.portrait.destroy');

            Route::put('/caracteristiques/{attribute}', [GmCharacterController::class, 'updateAttribute'])->name('attributes.update');
            Route::post('/revelations', [GmCharacterController::class, 'reveal'])->name('reveal.store');

            Route::put('/competences/{skill}', [GmCharacterController::class, 'updateSkill'])->name('skills.update');

            Route::post('/maitrises', [GmCharacterController::class, 'storeMastery'])->name('masteries.store');
            Route::put('/maitrises/{mastery}', [GmCharacterController::class, 'updateMastery'])->name('masteries.update');
            Route::delete('/maitrises/{mastery}', [GmCharacterController::class, 'destroyMastery'])->name('masteries.destroy');

            Route::put('/affinites/{affinity}', [GmCharacterController::class, 'updateAffinity'])->name('affinities.update');

            Route::post('/capacites', [GmCharacterController::class, 'storeAbility'])->name('abilities.store');
            Route::put('/capacites/{ability}', [GmCharacterController::class, 'updateAbility'])->name('abilities.update');
            Route::delete('/capacites/{ability}', [GmCharacterController::class, 'destroyAbility'])->name('abilities.destroy');

            Route::post('/etats', [GmCharacterController::class, 'storeState'])->name('states.store');
            Route::put('/etats/{state}', [GmCharacterController::class, 'updateState'])->name('states.update');
            Route::delete('/etats/{state}', [GmCharacterController::class, 'destroyState'])->name('states.destroy');

            Route::post('/inventaire', [GmCharacterController::class, 'storeItem'])->name('inventory.store');
            Route::put('/inventaire/{item}', [GmCharacterController::class, 'updateItem'])->name('inventory.update');
            Route::delete('/inventaire/{item}', [GmCharacterController::class, 'destroyItem'])->name('inventory.destroy');
        });

        Route::post('/messages', [GmSecretMessageController::class, 'store'])->name('messages.store');
        Route::post('/extractions/session', SessionExtractionController::class)->name('session-extractions.store');

        // Journal du MJ : même écran que celui des joueurs, notes privées.
        Route::get('/journal', [NoteController::class, 'index'])->name('notes.index');
        Route::post('/journal', [NoteController::class, 'store'])->name('notes.store');
        Route::put('/journal/{note}', [NoteController::class, 'update'])->name('notes.update');
        Route::delete('/journal/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

        // ── Base de PNJ ───────────────────────────────────────────────────
        // Import/export placés avant la route paramétrée pour que « importer »
        // ne soit jamais interprété comme un identifiant de PNJ.
        Route::get('/pnj', [GmNpcController::class, 'index'])->name('npcs.index');
        Route::post('/pnj', [GmNpcController::class, 'store'])->name('npcs.store');
        Route::get('/pnj/importer', [NpcImportController::class, 'show'])->name('npcs.import.show');
        Route::post('/pnj/importer/analyser', [NpcImportController::class, 'analyse'])->name('npcs.import.analyse');
        Route::post('/pnj/importer', [NpcImportController::class, 'store'])->name('npcs.import.store');
        Route::get('/pnj/exporter', [GmNpcController::class, 'export'])->name('npcs.export');

        Route::prefix('/pnj/{npc}')->group(function () {
            Route::get('/', [GmNpcController::class, 'show'])->name('npcs.detail');
            Route::put('/', [GmNpcController::class, 'update'])->name('npcs.detail.update');
            Route::delete('/', [GmNpcController::class, 'destroy'])->name('npcs.destroy');
            Route::post('/portrait', [NpcPortraitController::class, 'update'])->name('npcs.portrait.update');
            Route::delete('/portrait', [NpcPortraitController::class, 'destroy'])->name('npcs.portrait.destroy');
            Route::get('/exporter', [GmNpcController::class, 'export'])->name('npcs.export.one');

            Route::post('/secrets', [GmNpcController::class, 'storeSecret'])->name('npcs.secrets.store');
            Route::delete('/secrets/{secret}', [GmNpcController::class, 'destroySecret'])->name('npcs.secrets.destroy');

            Route::post('/informations', [GmNpcController::class, 'storeInformation'])->name('npcs.informations.store');
            Route::delete('/informations/{information}', [GmNpcController::class, 'destroyInformation'])->name('npcs.informations.destroy');
            Route::post('/informations/{information}/reveler', [GmNpcController::class, 'revealInformation'])->name('npcs.informations.reveal');

            Route::post('/reveler', [GmNpcController::class, 'reveal'])->name('npcs.detail.reveal');
        });

        Route::get('/monde', [GmWorldController::class, 'index'])->name('world.index');
        Route::post('/monde/cartes', [MapGridController::class, 'store'])->name('maps.store');
        Route::get('/monde/cartes/{map}/quadrillage', [MapGridController::class, 'show'])->name('maps.grid');
        Route::put('/monde/cartes/{map}/quadrillage', [MapGridController::class, 'updateGrid'])->name('maps.grid.update');
        Route::post('/monde/cartes/{map}/cases', [MapGridController::class, 'toggleCell'])->name('maps.cells.toggle');
        Route::post('/monde/cartes/{map}/cases/toutes', [MapGridController::class, 'toggleAllCells'])->name('maps.cells.all');
        Route::put('/monde/cartes/{map}/acces', [MapGridController::class, 'updateAccess'])->name('maps.access');
        Route::delete('/monde/cartes/{map}', [MapGridController::class, 'destroy'])->name('maps.destroy');
        Route::put('/monde/cartes/{map}', [GmWorldController::class, 'updateMap'])->name('maps.update');
        Route::post('/monde/cartes/{map}/reveler', [GmWorldController::class, 'revealMap'])->name('maps.reveal');
        Route::post('/monde/pnj', [GmWorldController::class, 'storeNpc'])->name('npcs.store');
        Route::put('/monde/pnj/{npc}', [GmWorldController::class, 'updateNpc'])->name('npcs.update');
        Route::post('/monde/pnj/{npc}/reveler', [GmWorldController::class, 'revealNpc'])->name('npcs.reveal');
    });
});
