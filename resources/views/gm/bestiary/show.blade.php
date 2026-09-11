@extends('layouts.app')

@section('title', $monster->name)
@section('context', 'Bestiaire du maître')

@section('content')
<div class="page-heading bestiary-heading">
    <div>
        <div class="eyebrow"><a href="{{ route('gm.bestiary.index') }}">← Retour au bestiaire</a></div>
        <h1>{{ $monster->name }}</h1>
        <p>{{ $monster->type ?: 'Type inconnu' }}@if($monster->threat_level) · {{ $monster->threat_level }}@endif</p>
    </div>
    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('gm.bestiary.export.one', $monster) }}">Exporter ce monstre</a>
        <form method="POST" action="{{ route('gm.bestiary.destroy', $monster) }}" onsubmit="return confirm('Supprimer définitivement ce monstre ?')">
            @csrf @method('DELETE')
            <button class="btn btn-danger" type="submit">Supprimer</button>
        </form>
    </div>
</div>

<div class="grid bestiary-admin-grid">
    <section class="card">
        <div class="monster-profile-portrait">
            @if($monster->portrait_path)
                <img src="{{ $monster->portrait_path }}" alt="Portrait de {{ $monster->name }}">
            @else
                {{ $monster->initials() }}
            @endif
        </div>
        <form method="POST" action="{{ route('gm.bestiary.update', $monster) }}" enctype="multipart/form-data" class="card-body">
            @csrf @method('PUT')
            <input type="hidden" name="name" value="{{ $monster->name }}">
            <div class="form-group">
                <label for="monster-portrait">Remplacer le portrait · JPEG, PNG ou WebP</label>
                <input class="input" id="monster-portrait" type="file" name="portrait" accept="image/jpeg,image/png,image/webp" required>
            </div>
            <button class="btn btn-secondary btn-sm" type="submit" style="margin-top:.7rem">Charger le portrait</button>
        </form>
        @if($monster->portrait_path)
            <form method="POST" action="{{ route('gm.bestiary.portrait.destroy', $monster) }}" class="card-body" style="padding-top:0">
                @csrf @method('DELETE')
                <button class="btn btn-ghost btn-sm danger" type="submit">Retirer le portrait</button>
            </form>
        @endif
    </section>

    <section class="card">
        <header class="card-header">
            <div><div class="eyebrow">Diffusion</div><h2>Révéler aux joueurs</h2></div>
            <span class="badge badge-gold">{{ $monster->discoveredBy->count() }}/{{ $players->count() }}</span>
        </header>
        <form method="POST" action="{{ route('gm.bestiary.reveal', $monster) }}" class="card-body">
            @csrf
            <label class="check bestiary-all-check"><input type="checkbox" name="all_players" value="1"> Toute la table</label>
            <div class="reveal-player-grid">
                @foreach($players as $player)
                    <label class="check">
                        <input type="checkbox" name="user_ids[]" value="{{ $player->id }}" @checked($monster->discoveredBy->contains('id', $player->id))>
                        {{ $player->character?->name ?: $player->name }}
                    </label>
                @endforeach
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:1rem">Ajouter à leur bestiaire</button>
        </form>
    </section>
</div>

<section class="card section">
    <header class="card-header"><div><div class="eyebrow">Données secrètes</div><h2>Fiche de combat</h2></div></header>
    <form method="POST" action="{{ route('gm.bestiary.update', $monster) }}" enctype="multipart/form-data" class="card-body">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group"><label>Nom</label><input class="input" name="name" value="{{ old('name', $monster->name) }}" required></div>
            <div class="form-group"><label>Type</label><input class="input" name="type" value="{{ old('type', $monster->type) }}"></div>
            <div class="form-group"><label>Taille</label><input class="input" name="size" value="{{ old('size', $monster->size) }}"></div>
            <div class="form-group"><label>Niveau de menace</label><input class="input" name="threat_level" value="{{ old('threat_level', $monster->threat_level) }}"></div>
            <div class="form-group"><label>Vie actuelle</label><input class="input" type="number" min="0" name="health_current" value="{{ old('health_current', $monster->health_current) }}"></div>
            <div class="form-group"><label>Vie maximale</label><input class="input" type="number" min="1" name="health_max" value="{{ old('health_max', $monster->health_max) }}"></div>
            <div class="form-group"><label>Mana actuel</label><input class="input" type="number" min="0" name="mana_current" value="{{ old('mana_current', $monster->mana_current) }}"></div>
            <div class="form-group"><label>Mana maximal</label><input class="input" type="number" min="0" name="mana_max" value="{{ old('mana_max', $monster->mana_max) }}"></div>
            <div class="form-group"><label>Armure</label><input class="input" type="number" min="0" name="armor" value="{{ old('armor', $monster->armor) }}"></div>
            <div class="form-group"><label>Formule de dégâts</label><input class="input" name="damage_dice" value="{{ old('damage_dice', $monster->damage_dice) }}" placeholder="2d6+3"></div>
            @foreach(['strength' => 'Force', 'endurance' => 'Endurance', 'dexterity' => 'Dextérité', 'intelligence' => 'Intelligence', 'willpower' => 'Volonté', 'perception' => 'Perception'] as $field => $label)
                <div class="form-group"><label>{{ $label }}</label><input class="input" type="number" min="0" max="999" name="{{ $field }}" value="{{ old($field, $monster->{$field}) }}"></div>
            @endforeach
            <div class="form-group full"><label>Description visible après révélation</label><textarea class="textarea" name="description">{{ old('description', $monster->description) }}</textarea></div>
            <div class="form-group"><label>Habitat</label><input class="input" name="habitat" value="{{ old('habitat', $monster->habitat) }}"></div>
            <div class="form-group full"><label>Comportement</label><textarea class="textarea" name="behavior">{{ old('behavior', $monster->behavior) }}</textarea></div>
            <div class="form-group full"><label>Point faible réel</label><textarea class="textarea" name="weakness">{{ old('weakness', $monster->weakness) }}</textarea></div>
            <div class="form-group full"><label>Notes MJ</label><textarea class="textarea" name="game_master_notes">{{ old('game_master_notes', $monster->game_master_notes) }}</textarea></div>
        </div>
        <div class="actions" style="margin-top:1rem">
            <button class="btn btn-primary" type="submit">Enregistrer la fiche</button>
            @if($monster->damage_dice)
                <button class="btn btn-secondary" type="button" data-dice-roll data-url="{{ route('gm.bestiary.damage.roll', $monster) }}">Lancer {{ $monster->damage_dice }}</button>
                <output class="dice-result" data-dice-result aria-live="polite"></output>
            @endif
        </div>
    </form>
</section>

<section class="section">
    <div class="section-title">
        <div><h2>Capacités</h2><p>Attaques, sorts et pouvoirs avec leur formule de dés.</p></div>
    </div>

    <div class="stack">
        @foreach($monster->abilities as $ability)
            <details class="card monster-ability-card" open>
                <summary class="card-header">
                    <div><span class="eyebrow">{{ $ability->type ?: 'Capacité' }}</span><h3>{{ $ability->name }}</h3></div>
                    <div class="actions">
                        @if($ability->dice_formula)<span class="badge badge-gold">{{ $ability->dice_formula }}</span>@endif
                        @if($ability->mana_cost)<span class="badge">{{ $ability->mana_cost }} mana</span>@endif
                    </div>
                </summary>
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
                    <div class="actions" style="margin-top:1rem">
                        <button class="btn btn-secondary" type="submit">Enregistrer</button>
                        @if($ability->dice_formula)
                            <button class="btn btn-primary" type="button" data-dice-roll data-url="{{ route('gm.bestiary.abilities.roll', [$monster, $ability]) }}">Lancer les dés</button>
                            <output class="dice-result" data-dice-result aria-live="polite"></output>
                        @endif
                    </div>
                </form>
                <form method="POST" action="{{ route('gm.bestiary.abilities.destroy', [$monster, $ability]) }}" class="card-body" style="padding-top:0">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm danger" type="submit">Supprimer la capacité</button>
                </form>
            </details>
        @endforeach

        <details class="card details-form">
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
                <button class="btn btn-primary" type="submit" style="margin-top:1rem">Ajouter</button>
            </form>
        </details>
    </div>
</section>
@endsection
