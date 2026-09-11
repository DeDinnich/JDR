@extends('layouts.app')

@section('title', $monster->name)
@section('context', 'Bestiaire du maître')

@section('content')
@php($healthPercent = $monster->health_max > 0 ? min(100, round(($monster->health_current / $monster->health_max) * 100)) : 0)
@php($manaPercent = $monster->mana_max > 0 ? min(100, round(($monster->mana_current / $monster->mana_max) * 100)) : 0)

<div class="page-heading bestiary-heading">
    <div>
        <div class="eyebrow"><a href="{{ route('gm.bestiary.index') }}">← Retour au bestiaire</a></div>
        <h1>{{ $monster->name }}</h1>
        <div class="monster-heading-meta">
            <span class="badge badge-gold">{{ $monster->type ?: 'Type inconnu' }}</span>
            @if($monster->size)<span class="badge">{{ $monster->size }}</span>@endif
            @if($monster->threat_level)<span class="badge badge-red">{{ $monster->threat_level }}</span>@endif
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('gm.bestiary.export.one', $monster) }}">Exporter</a>
        <form method="POST" action="{{ route('gm.bestiary.destroy', $monster) }}" onsubmit="return confirm('Supprimer définitivement ce monstre ?')">
            @csrf @method('DELETE')
            <button class="btn btn-danger" type="submit">Supprimer</button>
        </form>
    </div>
</div>

<nav class="monster-section-nav" aria-label="Sections du monstre">
    <a href="#combat">Combat</a>
    <a href="#caracteristiques">Caractéristiques</a>
    <a href="#informations">Informations</a>
    <a href="#capacites">Capacités <span>{{ $monster->abilities->count() }}</span></a>
</nav>

<div class="monster-admin-layout">
    <aside class="monster-admin-sidebar">
        <section class="card monster-portrait-card">
            <div class="monster-profile-portrait">
                @if($monster->portrait_path)
                    <img src="{{ $monster->portrait_path }}" alt="Portrait de {{ $monster->name }}">
                @else
                    {{ $monster->initials() }}
                @endif
            </div>
            <details class="monster-portrait-editor">
                <summary>Modifier le portrait</summary>
                <form method="POST" action="{{ route('gm.bestiary.update', $monster) }}" enctype="multipart/form-data" class="card-body">
                    @csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ $monster->name }}">
                    <div class="form-group">
                        <label for="monster-portrait">JPEG, PNG ou WebP · 4 Mo max.</label>
                        <input class="input" id="monster-portrait" type="file" name="portrait" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <button class="btn btn-secondary btn-sm" type="submit">Charger le portrait</button>
                </form>
                @if($monster->portrait_path)
                    <form method="POST" action="{{ route('gm.bestiary.portrait.destroy', $monster) }}" class="monster-portrait-remove">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm danger" type="submit">Retirer le portrait</button>
                    </form>
                @endif
            </details>
        </section>

        <section class="card monster-reveal-card">
            <header class="card-header">
                <div>
                    <div class="eyebrow">Diffusion</div>
                    <h2>Bestiaires joueurs</h2>
                </div>
                <span class="badge badge-gold">{{ $monster->discoveredBy->count() }}/{{ $players->count() }}</span>
            </header>
            <form method="POST" action="{{ route('gm.bestiary.reveal', $monster) }}" class="card-body">
                @csrf
                <label class="check bestiary-all-check"><input type="checkbox" name="all_players" value="1"> Toute la table</label>
                <div class="reveal-player-grid">
                    @foreach($players as $player)
                        <label class="check">
                            <input type="checkbox" name="user_ids[]" value="{{ $player->id }}" @checked($monster->discoveredBy->contains('id', $player->id))>
                            <span>{{ $player->character?->displayName() ?: $player->name }}</span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary monster-reveal-action" type="submit">Révéler la créature</button>
            </form>
        </section>
    </aside>

    <main class="monster-admin-content">
        <form method="POST" action="{{ route('gm.bestiary.update', $monster) }}" class="monster-sheet-form">
            @csrf @method('PUT')

            <section class="card monster-combat-card" id="combat">
                <header class="card-header">
                    <div>
                        <div class="eyebrow">Utilisation en partie</div>
                        <h2>Console de combat</h2>
                    </div>
                    <div class="actions">
                        <span class="badge">Armure {{ $monster->armor }}</span>
                        @if($monster->damage_dice)<span class="badge badge-red">Dégâts {{ $monster->damage_dice }}</span>@endif
                    </div>
                </header>
                <div class="card-body">
                    <div class="monster-resource-grid">
                        <section class="monster-resource monster-resource-health">
                            <div class="monster-resource-head">
                                <span>Vie</span>
                                <strong><output data-monster-current-output="health">{{ $monster->health_current }}</output> / <output data-monster-max-output="health">{{ $monster->health_max }}</output></strong>
                            </div>
                            <div class="gauge gauge-health"><span data-monster-gauge="health" style="width:{{ $healthPercent }}%"></span></div>
                            <div class="monster-resource-inputs">
                                <label>Actuelle<input class="input" type="number" min="0" name="health_current" value="{{ old('health_current', $monster->health_current) }}" data-monster-resource-current="health"></label>
                                <label>Maximum<input class="input" type="number" min="1" name="health_max" value="{{ old('health_max', $monster->health_max) }}" data-monster-resource-max="health"></label>
                            </div>
                        </section>

                        <section class="monster-resource monster-resource-mana">
                            <div class="monster-resource-head">
                                <span>Mana</span>
                                <strong><output data-monster-current-output="mana">{{ $monster->mana_current }}</output> / <output data-monster-max-output="mana">{{ $monster->mana_max }}</output></strong>
                            </div>
                            <div class="gauge gauge-mana"><span data-monster-gauge="mana" style="width:{{ $manaPercent }}%"></span></div>
                            <div class="monster-resource-inputs">
                                <label>Actuel<input class="input" type="number" min="0" name="mana_current" value="{{ old('mana_current', $monster->mana_current) }}" data-monster-resource-current="mana"></label>
                                <label>Maximum<input class="input" type="number" min="0" name="mana_max" value="{{ old('mana_max', $monster->mana_max) }}" data-monster-resource-max="mana"></label>
                            </div>
                        </section>
                    </div>

                    <div class="monster-combat-fields">
                        <div class="form-group">
                            <label for="monster-armor">Armure</label>
                            <input class="input" id="monster-armor" type="number" min="0" max="999" name="armor" value="{{ old('armor', $monster->armor) }}">
                        </div>
                        <div class="form-group">
                            <label for="monster-damage">Formule de dégâts</label>
                            <input class="input" id="monster-damage" name="damage_dice" value="{{ old('damage_dice', $monster->damage_dice) }}" placeholder="2d6+3">
                        </div>
                        <div class="actions monster-damage-roll">
                            @if($monster->damage_dice)
                                <button class="btn btn-secondary" type="button" data-dice-roll data-url="{{ route('gm.bestiary.damage.roll', $monster) }}">Lancer {{ $monster->damage_dice }}</button>
                                <output class="dice-result" data-dice-result aria-live="polite"></output>
                            @else
                                <span class="small muted">Ajoute une formule puis enregistre-la pour activer le jet.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="card monster-stats-card" id="caracteristiques">
                <header class="card-header">
                    <div>
                        <div class="eyebrow">Valeurs principales</div>
                        <h2>Caractéristiques</h2>
                    </div>
                </header>
                <div class="card-body monster-stat-grid">
                    @foreach([
                        'strength' => ['FOR', 'Force'],
                        'endurance' => ['END', 'Endurance'],
                        'dexterity' => ['DEX', 'Dextérité'],
                        'intelligence' => ['INT', 'Intelligence'],
                        'willpower' => ['VOL', 'Volonté'],
                        'perception' => ['PER', 'Perception'],
                    ] as $field => [$abbreviation, $label])
                        <label class="monster-stat-field">
                            <span class="stat-abbr">{{ $abbreviation }}</span>
                            <span class="stat-name">{{ $label }}</span>
                            <input type="number" min="0" max="999" name="{{ $field }}" value="{{ old($field, $monster->{$field}) }}" aria-label="{{ $label }}">
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="monster-information-grid" id="informations">
                <section class="card">
                    <header class="card-header">
                        <div>
                            <div class="eyebrow">Visible après révélation</div>
                            <h2>Identité publique</h2>
                        </div>
                    </header>
                    <div class="card-body form-grid">
                        <div class="form-group"><label>Nom</label><input class="input" name="name" value="{{ old('name', $monster->name) }}" required></div>
                        <div class="form-group"><label>Type</label><input class="input" name="type" value="{{ old('type', $monster->type) }}"></div>
                        <div class="form-group"><label>Taille</label><input class="input" name="size" value="{{ old('size', $monster->size) }}"></div>
                        <div class="form-group"><label>Niveau de menace</label><input class="input" name="threat_level" value="{{ old('threat_level', $monster->threat_level) }}"></div>
                        <div class="form-group full"><label>Description visible</label><textarea class="textarea" name="description" rows="5">{{ old('description', $monster->description) }}</textarea></div>
                    </div>
                </section>

                <section class="card monster-secret-card">
                    <header class="card-header">
                        <div>
                            <div class="eyebrow">Réservé au maître du jeu</div>
                            <h2>Tactique et secrets</h2>
                        </div>
                    </header>
                    <div class="card-body form-grid">
                        <div class="form-group full"><label>Habitat</label><input class="input" name="habitat" value="{{ old('habitat', $monster->habitat) }}"></div>
                        <div class="form-group full"><label>Comportement</label><textarea class="textarea" name="behavior" rows="4">{{ old('behavior', $monster->behavior) }}</textarea></div>
                        <div class="form-group full"><label>Point faible réel</label><textarea class="textarea" name="weakness" rows="4">{{ old('weakness', $monster->weakness) }}</textarea></div>
                        <div class="form-group full"><label>Notes MJ</label><textarea class="textarea" name="game_master_notes" rows="5">{{ old('game_master_notes', $monster->game_master_notes) }}</textarea></div>
                    </div>
                </section>
            </div>

            <div class="monster-save-bar">
                <span class="muted small">Les données de cette fiche restent invisibles aux joueurs.</span>
                <button class="btn btn-primary" type="submit">Enregistrer la fiche</button>
            </div>
        </form>

        <section class="monster-abilities-section" id="capacites">
            <div class="section-title">
                <div>
                    <h2>Capacités</h2>
                    <p>{{ $monster->abilities->count() }} attaque(s), sort(s) ou pouvoir(s) configuré(s).</p>
                </div>
            </div>

            <div class="stack monster-ability-list">
                @foreach($monster->abilities as $ability)
                    <details class="card monster-ability-card">
                        <summary class="card-header">
                            <div>
                                <span class="eyebrow">{{ $ability->type ?: 'Capacité' }}</span>
                                <h3>{{ $ability->name }}</h3>
                                @if($ability->description)<p>{{ $ability->description }}</p>@endif
                            </div>
                            <div class="monster-ability-meta">
                                @if($ability->dice_formula)<span class="badge badge-gold">{{ $ability->dice_formula }}</span>@endif
                                @if($ability->mana_cost)<span class="badge badge-blue">{{ $ability->mana_cost }} mana</span>@endif
                                @if($ability->cooldown)<span class="badge">{{ $ability->cooldown }}</span>@endif
                                <span class="monster-details-chevron" aria-hidden="true">⌄</span>
                            </div>
                        </summary>
                        <div class="monster-ability-body">
                            @if($ability->dice_formula)
                                <div class="monster-ability-roll actions">
                                    <button class="btn btn-primary" type="button" data-dice-roll data-url="{{ route('gm.bestiary.abilities.roll', [$monster, $ability]) }}">Lancer {{ $ability->dice_formula }}</button>
                                    <output class="dice-result" data-dice-result aria-live="polite"></output>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('gm.bestiary.abilities.update', [$monster, $ability]) }}" class="card-body">
                                @csrf @method('PUT')
                                <div class="form-grid">
                                    <div class="form-group"><label>Nom</label><input class="input" name="name" value="{{ $ability->name }}" required></div>
                                    <div class="form-group"><label>Type</label><input class="input" name="type" value="{{ $ability->type }}"></div>
                                    <div class="form-group"><label>Formule de dés</label><input class="input" name="dice_formula" value="{{ $ability->dice_formula }}" placeholder="2d8+4"></div>
                                    <div class="form-group"><label>Coût en mana</label><input class="input" type="number" min="0" name="mana_cost" value="{{ $ability->mana_cost }}"></div>
                                    <div class="form-group"><label>Recharge</label><input class="input" name="cooldown" value="{{ $ability->cooldown }}" placeholder="1 tour, 1 fois par combat…"></div>
                                    <div class="form-group"><label>Ordre</label><input class="input" type="number" min="0" name="sort_order" value="{{ $ability->sort_order }}"></div>
                                    <div class="form-group full"><label>Description et effets</label><textarea class="textarea" name="description">{{ $ability->description }}</textarea></div>
                                </div>
                                <div class="actions monster-ability-actions">
                                    <button class="btn btn-secondary" type="submit">Enregistrer</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('gm.bestiary.abilities.destroy', [$monster, $ability]) }}" class="monster-ability-delete" onsubmit="return confirm('Supprimer cette capacité ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm danger" type="submit">Supprimer la capacité</button>
                            </form>
                        </div>
                    </details>
                @endforeach

                <details class="card details-form monster-new-ability">
                    <summary class="card-header"><h3>＋ Ajouter une capacité</h3></summary>
                    <form method="POST" action="{{ route('gm.bestiary.abilities.store', $monster) }}" class="card-body">
                        @csrf
                        <div class="form-grid">
                            <div class="form-group"><label>Nom</label><input class="input" name="name" required></div>
                            <div class="form-group"><label>Type</label><input class="input" name="type" placeholder="Attaque, sort, réaction…"></div>
                            <div class="form-group"><label>Formule de dés</label><input class="input" name="dice_formula" placeholder="2d8+4"></div>
                            <div class="form-group"><label>Coût en mana</label><input class="input" type="number" min="0" name="mana_cost" value="0"></div>
                            <div class="form-group"><label>Recharge</label><input class="input" name="cooldown"></div>
                            <div class="form-group"><label>Ordre</label><input class="input" type="number" min="0" name="sort_order" value="{{ $monster->abilities->count() }}"></div>
                            <div class="form-group full"><label>Description et effets</label><textarea class="textarea" name="description"></textarea></div>
                        </div>
                        <button class="btn btn-primary" type="submit" style="margin-top:1rem">Ajouter la capacité</button>
                    </form>
                </details>
            </div>
        </section>
    </main>
</div>
@endsection
