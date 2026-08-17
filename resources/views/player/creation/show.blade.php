@extends('layouts.app')

@section('title', 'Naissance')
@section('content')

@if($step === 'identity')
<div class="page-heading">
    <div class="eyebrow">Royaume d’Ashura · nuit d’orage</div>
    <h1>Une nouvelle vie commence</h1>
    <p>Tu ouvres les yeux dans un monde qui n’est pas le tien. Tu ne sais encore ni marcher, ni parler — seulement observer. Donne-toi un nom : tout le reste se construira au fil de ton enfance.</p>
</div>

<section class="section card">
    <header class="card-header"><h2>Identité</h2><span class="badge">Étape 1 sur 2</span></header>
    <div class="card-body">
        <form method="POST" action="{{ route('player.creation.store') }}" class="form">
            @csrf

            <div class="field">
                <label for="first_name">Prénom</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required autofocus maxlength="60">
                @error('first_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="last_name">Nom <span class="eyebrow">(facultatif — ton origine peut t’en donner un)</span></label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" maxlength="60">
                @error('last_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="gender">Genre <span class="eyebrow">(facultatif)</span></label>
                <input type="text" id="gender" name="gender" value="{{ old('gender') }}" maxlength="32">
                @error('gender')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <p class="metric-note">Ni classe, ni métier, ni statistiques : tu n’es qu’un nourrisson. Tes caractéristiques se révéleront au fil de ce que tu vivras.</p>

            <button type="submit" class="button">Naître</button>
        </form>
    </div>
</section>

@else
{{--
    Choix de l'origine.

    La fenêtre n'a ni croix ni fond cliquable : tant qu'aucune maison n'est
    choisie, le joueur n'a rien à faire ailleurs. Une maison prise par
    quelqu'un d'autre se grise en direct (voir App\Events\HouseTaken).
--}}
<div class="page-heading">
    <div class="eyebrow">{{ $character->displayName() }} · nouveau-né</div>
    <h1>D’où viens-tu ?</h1>
</div>

<div class="modal-overlay is-locked" role="dialog" aria-modal="true" aria-labelledby="house-choice-title">
    <div class="modal-panel card modal-panel-wide">
        <header class="card-header">
            <div>
                <span class="eyebrow">Les grandes maisons du château</span>
                <h2 id="house-choice-title">Choisis ta famille</h2>
            </div>
        </header>

        <div class="card-body">
            @error('house')<p class="form-error">{{ $message }}</p>@enderror

            <p class="muted">
                Les grandes familles du royaume ont été rassemblées au château.
                Chacune élève ses enfants à sa manière — ce choix est définitif.
            </p>

            <div class="grid grid-3 house-choice" data-house-choice>
                @foreach($houses as $house)
                    <form method="POST" action="{{ route('player.creation.choose') }}"
                          class="house-option {{ $house['is_taken'] ? 'is-taken' : '' }}"
                          data-house="{{ $house['slug'] }}">
                        @csrf
                        <input type="hidden" name="house" value="{{ $house['slug'] }}">

                        <article class="card" @if($house['color']) style="border-top:3px solid {{ $house['color'] }}" @endif>
                            <div class="card-body">
                                <div class="eyebrow">{{ $house['specialty'] }}</div>
                                <h3>{{ $house['name'] }}</h3>
                                @if($house['motto'])<p class="metric-note"><em>« {{ $house['motto'] }} »</em></p>@endif
                                <p class="small">{{ $house['description'] }}</p>
                                <p class="metric-note">{{ $house['reputation'] }}</p>

                                <span class="badge house-taken-badge">Déjà choisie</span>
                                <button class="btn btn-primary btn-sm house-choose-btn" type="submit"
                                        @disabled($house['is_taken'])>Grandir ici</button>
                            </div>
                        </article>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

@endsection
