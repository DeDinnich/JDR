@extends('layouts.app')

{{--
    Carte côté joueur.

    Les cases fermées ne sont pas floutées : leur image n'est simplement pas
    dans la page. Le joueur peut basculer de carte et poser ses propres
    repères, qui n'appartiennent qu'à lui.
--}}

@section('title', $map->title)
@section('context', 'Cartes')
@section('content')
<div class="page-heading">
    <div class="eyebrow">Monde</div>
    <h1>{{ $map->title }}</h1>
    <p>{{ $map->description }}</p>
</div>

{{-- Sélecteur de carte : on change de région sans repasser par l'index. --}}
@if($maps->count() > 1)
    <div class="actions" style="margin-bottom:1rem">
        @foreach($maps as $option)
            <a class="btn {{ $option->is($map) ? 'btn-primary' : 'btn-secondary' }} btn-sm"
               href="{{ route('player.world.show', $option) }}">{{ $option->title }}</a>
        @endforeach
    </div>
@endif

@if($map->hasGrid())
    <section class="card">
        <div class="map-toolbar" role="toolbar" aria-label="Outils de carte">
            <button type="button" class="btn btn-secondary btn-sm" data-point-mode aria-pressed="false">
                ✚ Poser un repère
            </button>
            <label class="check">
                Couleur
                <input type="color" data-point-color value="#c9a227" aria-label="Couleur du repère">
            </label>
            <label class="check">
                <input type="checkbox" data-toggle-labels checked> Afficher les noms
            </label>
            <span class="metric-note" data-point-hint>Active l’outil puis clique sur la carte.</span>
        </div>

        <div class="card-body">
            <x-map-grid :map="$map" :revealed="$revealed" :points="$points" />
        </div>
    </section>

    <section class="card section">
        <header class="card-header">
            <h2>Tes repères</h2>
            <span class="badge">{{ $points->where('user_id', auth()->id())->count() }}</span>
        </header>
        <div class="card-body list" data-point-list>
            @forelse($points as $point)
                <div class="list-row" data-point-row="{{ $point->id }}">
                    <div>
                        <span class="map-point-swatch" style="background:{{ $point->color }}"></span>
                        <strong>{{ $point->label }}</strong>
                        @if($point->user_id !== auth()->id())
                            <span class="badge badge-gold">Maître du jeu</span>
                        @endif
                    </div>
                    @if($point->user_id === auth()->id())
                        <button class="btn btn-ghost btn-sm" type="button"
                                data-delete-point="{{ route('maps.points.destroy', [$map, $point]) }}"
                                title="Retirer">🗑</button>
                    @endif
                </div>
            @empty
                <div class="empty">Aucun repère sur cette carte.</div>
            @endforelse
        </div>
    </section>
@else
    <div class="card empty">Cette carte n’a pas encore d’image.</div>
@endif
@endsection
