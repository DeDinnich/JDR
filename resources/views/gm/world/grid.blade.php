@extends('layouts.app')

{{--
    Pilotage d'une carte quadrillée — vue MJ.
    Cliquer une case l'ouvre ou la referme immédiatement, sans rechargement.
--}}

@section('title', $map->title)
@section('context', 'Table du maître du jeu')

@section('content')
<div class="page-heading">
    <div class="eyebrow"><a href="{{ route('gm.world.index') }}">← Retour au monde</a></div>
    <h1>{{ $map->title }}</h1>
    <p>{{ $map->description }}</p>
</div>

<div class="actions" style="margin-bottom:1rem">
    <form method="POST" action="{{ route('gm.maps.cells.all', $map) }}">
        @csrf<input type="hidden" name="reveal" value="1">
        <button class="btn btn-secondary btn-sm" type="submit">Tout révéler</button>
    </form>
    <form method="POST" action="{{ route('gm.maps.cells.all', $map) }}">
        @csrf<input type="hidden" name="reveal" value="0">
        <button class="btn btn-secondary btn-sm" type="submit">Tout masquer</button>
    </form>
    <form method="POST" action="{{ route('gm.maps.destroy', $map) }}" onsubmit="return confirm('Supprimer la carte et ses tuiles ?')">
        @csrf @method('DELETE')
        <button class="btn btn-ghost btn-sm" type="submit">🗑 Supprimer</button>
    </form>
</div>

{{-- ── Qui possède cette carte ──────────────────────────────────────── --}}
<section class="card" style="margin-bottom:1rem">
    <header class="card-header">
        <div><h2>Qui possède cette carte</h2><p class="muted small" style="margin:.2rem 0 0">Décocher un joueur lui retire la carte. Les cases ouvertes sont conservées.</p></div>
        <span class="badge badge-gold">{{ $map->discoveredBy->count() }} / {{ $players->count() }}</span>
    </header>
    <form method="POST" action="{{ route('gm.maps.access', $map) }}" class="card-body">
        @csrf @method('PUT')
        <div class="gm-inline-form">
            @forelse($players as $player)
                <label class="check">
                    <input type="checkbox" name="user_ids[]" value="{{ $player->id }}"
                           @checked($map->discoveredBy->contains($player->id))>
                    {{ $player->name }}
                </label>
            @empty
                <span class="muted">Aucun joueur inscrit.</span>
            @endforelse
            <button class="btn btn-primary btn-sm" type="submit">Enregistrer les accès</button>
        </div>
    </form>
</section>

@if($map->hasGrid())
    <section class="card">
        <header class="card-header">
            <div>
                <h2>Quadrillage</h2>
                <p class="muted small" style="margin:.2rem 0 0">
                    Clique une case pour l’ouvrir ou la refermer. {{ count($revealed) }} / {{ $map->grid_columns * $map->grid_rows }} ouverte(s).
                    Les cases fermées te restent visibles en sombre — les joueurs, eux, n’en reçoivent rien.
                </p>
            </div>
        </header>

        <div class="card-body">
            {{-- Filtre d'affichage des repères : le MJ coche qui il veut voir. --}}
            <div class="gm-inline-form" style="margin-bottom:.8rem">
                <span class="eyebrow">Voir les repères de</span>
                @foreach($players as $player)
                    <label class="check">
                        <input type="checkbox" data-point-filter value="{{ $player->id }}" checked> {{ $player->name }}
                    </label>
                @endforeach
                <label class="check">
                    <input type="checkbox" data-point-filter value="{{ auth()->id() }}" checked> Les miens
                </label>
            </div>

            <x-map-grid :map="$map" :revealed="$revealed" :points="$map->points" :editable="true" />

            <p class="metric-note" style="margin-top:.6rem">
                Maj + clic sur la carte pour poser un repère.
            </p>
        </div>
    </section>

    <section class="card section">
        <header class="card-header"><h2>Refaire le quadrillage</h2></header>
        <form method="POST" action="{{ route('gm.maps.grid.update', $map) }}" class="card-body">
            @csrf @method('PUT')
            <div class="gm-inline-form">
                <div class="form-group"><label>Colonnes</label><input class="input input-xs" type="number" min="1" max="40" name="grid_columns" value="{{ $map->grid_columns }}" required></div>
                <div class="form-group"><label>Lignes</label><input class="input input-xs" type="number" min="1" max="40" name="grid_rows" value="{{ $map->grid_rows }}" required></div>
                <button class="btn btn-secondary btn-sm" type="submit">Redécouper</button>
            </div>
            <p class="metric-note">Les cases déjà ouvertes n’auraient plus de sens sur une autre grille : elles seront toutes refermées.</p>
        </form>
    </section>
@else
    <div class="card empty">Cette carte n’a pas d’image découpée. Réimporte-la depuis l’espace Monde.</div>
@endif
@endsection
