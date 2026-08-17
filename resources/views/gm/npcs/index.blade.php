@extends('layouts.app')

{{--
    Base de PNJ — vue MJ.

    Une galerie de visages, pensée pour la partie en direct : on cherche, on
    clique, la fiche s'ouvre en modale par-dessus la galerie sans changer de
    page. Le rattachement à un lieu n'est volontairement pas géré ici.

    La modale est en CSS pur (une case à cocher masquée pilote son affichage) :
    elle fonctionne donc même si le JavaScript n'a pas encore chargé.
--}}

@section('title', 'PNJ')
@section('context', 'Table du maître du jeu')

@section('content')
<div class="page-heading">
    <h1>PNJ</h1>
    <p>{{ $npcs->count() }} figure(s) dans les coulisses. Clique sur un visage pour ouvrir sa fiche.</p>
</div>

<div class="actions" style="margin-bottom:1rem">
    <a class="btn btn-primary" href="{{ route('gm.npcs.import.show') }}">Importer des PNJ</a>
    <a class="btn btn-secondary" href="{{ route('gm.npcs.export') }}">Exporter en JSON</a>
    <label class="btn btn-secondary" for="npc-create">＋ Créer un PNJ</label>
</div>

<section class="card">
    <form method="GET" action="{{ route('gm.npcs.index') }}" class="card-body">
        <div class="gm-inline-form">
            <div class="form-group" style="flex:1;min-width:14rem">
                <label for="recherche">Recherche</label>
                <input class="input" id="recherche" name="recherche" value="{{ $search }}" placeholder="Nom, rôle, profession">
            </div>
            <div class="form-group">
                <label for="maison">Maison</label>
                <select class="select" id="maison" name="maison">
                    <option value="">Toutes</option>
                    @foreach($houses as $house)
                        <option value="{{ $house->slug }}" @selected(($filters['maison'] ?? '') === $house->slug)>{{ $house->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="importance">Importance</label>
                <select class="select" id="importance" name="importance">
                    <option value="">Toutes</option>
                    @foreach($importances as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['importance'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="statut">Statut</label>
                <select class="select" id="statut" name="statut">
                    <option value="">Tous</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['statut'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary btn-sm" type="submit">Filtrer</button>
        </div>
    </form>
</section>

{{-- ── Galerie ─────────────────────────────────────────────────────────── --}}
<section class="section">
    <div class="grid grid-3">
        @forelse($npcs as $npc)
            <label class="card card-link card-body npc-tile" for="npc-{{ $npc->id }}">
                <div class="npc-row">
                    <span class="npc-avatar">
                        @if($npc->portrait_path)
                            <img src="{{ $npc->portrait_path }}" alt="">
                        @else
                            {{ $npc->initials() }}
                        @endif
                    </span>
                    <div>
                        <span class="eyebrow">{{ $npc->role ?: $npc->profession ?: 'Sans rôle' }}</span>
                        <h3 class="display" style="font-size:1.2rem;margin:.25rem 0">{{ $npc->fullName() }}</h3>
                        @if($npc->house)<span class="muted small">{{ $npc->house->name }}</span>@endif
                    </div>
                </div>

                <p class="muted small">{{ Str::limit($npc->description, 100) ?: 'Aucune description.' }}</p>

                <div class="actions">
                    <span class="badge">{{ $npc->importance->label() }}</span>
                    <span class="badge">{{ $npc->status->label() }}</span>
                    <span class="badge badge-gold">Connu de {{ $npc->discoveredBy->count() }}/{{ $players->count() }}</span>
                </div>

                @if($npc->tags)
                    <div class="actions">
                        @foreach($npc->tags as $tag)<span class="badge">{{ $tag }}</span>@endforeach
                    </div>
                @endif
            </label>
        @empty
            <div class="card empty span-2">Aucun PNJ ne correspond à cette recherche.</div>
        @endforelse
    </div>
</section>

{{-- ── Modales ─────────────────────────────────────────────────────────── --}}
@foreach($npcs as $npc)
    <input class="modal-toggle" type="checkbox" id="npc-{{ $npc->id }}" hidden>
    <div class="modal-overlay" role="dialog" aria-modal="true" aria-label="Fiche de {{ $npc->fullName() }}">
        {{-- Le fond ferme la modale : c'est le même label que le bouton ✕ --}}
        <label class="modal-backdrop" for="npc-{{ $npc->id }}" aria-hidden="true"></label>

        <div class="modal-panel card">
            <header class="card-header">
                <div>
                    <span class="eyebrow">{{ $npc->role ?: $npc->profession ?: 'Sans rôle' }}</span>
                    <h2>{{ $npc->fullName() }}</h2>
                </div>
                <label class="btn btn-ghost btn-sm" for="npc-{{ $npc->id }}" title="Fermer">✕</label>
            </header>

            <div class="card-body">
                <div class="actions" style="margin-bottom:.8rem">
                    <span class="badge">{{ $npc->importance->label() }}</span>
                    <span class="badge">{{ $npc->status->label() }}</span>
                    @if($npc->house)<span class="badge badge-gold">{{ $npc->house->name }}</span>@endif
                    @if($npc->age)<span class="badge">{{ $npc->age }} ans</span>@endif
                    @if($npc->race)<span class="badge">{{ $npc->race }}</span>@endif
                </div>

                @if($npc->nickname)<p><span class="eyebrow">Surnom</span><br>« {{ $npc->nickname }} »</p>@endif
                @if($npc->title)<p><span class="eyebrow">Titre</span><br>{{ $npc->title }}</p>@endif
                @if($npc->description)<p><span class="eyebrow">Description publique</span><br>{{ $npc->description }}</p>@endif
                @if($npc->personality)<p><span class="eyebrow">Personnalité</span><br>{{ $npc->personality }}</p>@endif
                @if($npc->game_master_notes)
                    <p><span class="eyebrow">Notes MJ <span class="badge badge-red">Jamais révélées</span></span><br>{{ $npc->game_master_notes }}</p>
                @endif

                <p><span class="eyebrow">Connu de</span><br>
                    {{ $npc->discoveredBy->pluck('name')->implode(', ') ?: 'personne pour l’instant' }}</p>
            </div>

            <footer class="card-body" style="padding-top:0">
                <div class="actions">
                    <a class="btn btn-primary btn-sm" href="{{ route('gm.npcs.detail', $npc) }}">Modifier / révéler</a>
                    <a class="btn btn-secondary btn-sm" href="{{ route('gm.npcs.export.one', $npc) }}">Exporter</a>
                </div>
            </footer>
        </div>
    </div>
@endforeach

{{-- ── Création ────────────────────────────────────────────────────────── --}}
<input class="modal-toggle" type="checkbox" id="npc-create" hidden>
<div class="modal-overlay" role="dialog" aria-modal="true" aria-label="Créer un PNJ">
    <label class="modal-backdrop" for="npc-create" aria-hidden="true"></label>
    <div class="modal-panel card">
        <header class="card-header">
            <h2>Créer un PNJ</h2>
            <label class="btn btn-ghost btn-sm" for="npc-create" title="Fermer">✕</label>
        </header>
        <form method="POST" action="{{ route('gm.npcs.store') }}" class="card-body">
            @csrf
            <div class="form-grid">
                <div class="form-group"><label>Prénom</label><input class="input" name="first_name" required></div>
                <div class="form-group"><label>Nom</label><input class="input" name="last_name"></div>
                <div class="form-group"><label>Rôle</label><input class="input" name="role"></div>
                <div class="form-group">
                    <label>Importance</label>
                    <select class="select" name="importance">
                        @foreach($importances as $value => $label)<option value="{{ $value }}" @selected($value === 'secondaire')>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select class="select" name="status">
                        @foreach($statuses as $value => $label)<option value="{{ $value }}" @selected($value === 'vivant')>{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.8rem">Créer</button>
        </form>
    </div>
</div>
@endsection
