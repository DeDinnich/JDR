@extends('layouts.app')

@section('title', $monster['name'])
@section('context', 'Bestiaire personnel')

@section('content')
<div class="page-heading">
    <div class="eyebrow"><a href="{{ route('player.bestiary.index') }}">← Retour au bestiaire</a></div>
    <h1>{{ $monster['name'] }}</h1>
    <p>{{ $monster['type'] ?: 'Nature inconnue' }}@if($monster['size']) · {{ $monster['size'] }}@endif</p>
</div>

<section class="card monster-player-hero">
    <div class="monster-profile-portrait">
        @if($monster['portrait_path'])
            <img src="{{ $monster['portrait_path'] }}" alt="Portrait de {{ $monster['name'] }}">
        @else
            {{ $monster['initials'] }}
        @endif
    </div>
    <div class="card-body">
        <div class="eyebrow">Première observation</div>
        <p>{{ $monster['description'] ?: 'Son apparence ne suffit pas encore à comprendre sa nature.' }}</p>
        <p class="muted small">La fiche ci-dessous est ton carnet d’hypothèses. Elle ne révèle aucune statistique du maître du jeu.</p>
    </div>
</section>

<section class="card section">
    <header class="card-header">
        <div><div class="eyebrow">Tes déductions</div><h2>Ce que tu crois savoir</h2></div>
    </header>
    <form method="POST" action="{{ route('player.bestiary.update', $monster['id']) }}" class="card-body">
        @csrf @method('PUT')
        <div class="form-grid bestiary-knowledge-grid">
            <div class="form-group">
                <label for="known-health">Vie estimée</label>
                <input class="input" id="known-health" name="known_health" value="{{ old('known_health', $monster['knowledge']['health']) }}" placeholder="Environ 40 PV, blessé à moitié…">
            </div>
            <div class="form-group">
                <label for="known-mana">Réserve de mana estimée</label>
                <input class="input" id="known-mana" name="known_mana" value="{{ old('known_mana', $monster['knowledge']['mana']) }}" placeholder="Faible, au moins 20…">
            </div>
            <div class="form-group">
                <label for="known-damage">Dégâts observés</label>
                <input class="input" id="known-damage" name="known_damage" value="{{ old('known_damage', $monster['knowledge']['damage']) }}" placeholder="Une morsure inflige 8 à 15…">
            </div>
            <div class="form-group full">
                <label for="known-abilities">Capacités observées</label>
                <textarea class="textarea" id="known-abilities" name="known_abilities" rows="5" placeholder="Décris ses attaques, sorts, réactions et habitudes…">{{ old('known_abilities', $monster['knowledge']['abilities']) }}</textarea>
            </div>
            <div class="form-group full">
                <label for="known-weakness">Points faibles supposés</label>
                <textarea class="textarea" id="known-weakness" name="known_weakness" rows="4" placeholder="Ce qui semble le ralentir, le blesser ou l’effrayer…">{{ old('known_weakness', $monster['knowledge']['weakness']) }}</textarea>
            </div>
            <div class="form-group full">
                <label for="monster-notes">Notes libres</label>
                <textarea class="textarea" id="monster-notes" name="personal_notes" rows="8" placeholder="Comportement, habitat, tactiques, circonstances de la rencontre…">{{ old('personal_notes', $monster['knowledge']['notes']) }}</textarea>
            </div>
        </div>
        <button class="btn btn-primary" type="submit" style="margin-top:1rem">Enregistrer mes déductions</button>
    </form>
</section>
@endsection
