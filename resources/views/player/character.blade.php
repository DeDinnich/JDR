@extends('layouts.app')

{{--
    Fiche personnage — vue joueur.

    Cette vue ne reçoit QUE le tableau $sheet produit par
    CharacterSheetPresenter::forPlayer(). Le modèle Character n'est pas
    accessible ici : il est donc structurellement impossible d'afficher, ou de
    laisser fuiter dans le HTML, une donnée que le personnage n'a pas découverte.
--}}

@section('title', $sheet['identity']['name'])
@section('context', 'Carnet d’aventurier')

@section('content')
@php($identity = $sheet['identity'])
@php($resources = $sheet['resources'])

<section class="card sheet-hero">
    <div class="sheet-portrait">
        @if($identity['portrait_path'])
            <img src="{{ $identity['portrait_path'] }}" alt="Portrait de {{ $identity['name'] }}">
        @else
            {{ $identity['initials'] }}
        @endif
    </div>

    <div class="sheet-identity">
        <div class="eyebrow">{{ $identity['adventurer_title'] ?: 'Sans titre' }}</div>
        <h1>{{ $identity['name'] }}</h1>

        <p class="sheet-traits">
            <span>{{ $identity['age_label'] }}</span>
            @if($identity['race'])<span>{{ $identity['race'] }}</span>@endif
            @if($identity['origin'])<span>{{ $identity['origin'] }}</span>@endif
            @if($identity['occupation'])<span>{{ $identity['occupation'] }}</span>@endif
        </p>

        <x-sheet.resource-sliders :resources="$resources" :character-id="$identity['id']" />

        @if($sheet['states']->isNotEmpty())
            <div class="actions" style="margin-top:.9rem">
                @foreach($sheet['states'] as $state)
                    <span class="state-chip" @if($state['description']) title="{{ $state['description'] }}" @endif>
                        @if($state['icon'])<span class="state-icon">{{ $state['icon'] }}</span>@endif
                        {{ $state['name'] }}
                        @if($state['duration_label'])<span class="muted small">· {{ $state['duration_label'] }}</span>@endif
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- Navigation interne : onglets en CSS pur, pour éviter une fiche de 3 km --}}
<div>
    <input class="sheet-tabs" type="radio" name="sheet-tab" id="tab-overview" checked>
    <input class="sheet-tabs" type="radio" name="sheet-tab" id="tab-masteries">
    <input class="sheet-tabs" type="radio" name="sheet-tab" id="tab-abilities">
    <input class="sheet-tabs" type="radio" name="sheet-tab" id="tab-story">
    <input class="sheet-tabs" type="radio" name="sheet-tab" id="tab-allies">

    <nav class="tab-bar" aria-label="Sections de la fiche">
        <label for="tab-overview">Compétences</label>
        <label for="tab-masteries">Maîtrises</label>
        <label for="tab-abilities">Capacités</label>
        <label for="tab-story">Histoire</label>
        <label for="tab-allies">Autres joueurs</label>
    </nav>

    <div class="sheet-panels">

        {{-- ── Vue d'ensemble ──────────────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="section-title">
                <div>
                    <h2>Caractéristiques</h2>
                    <p>Ce que tu sais de toi. Le reste se découvrira en vivant.</p>
                </div>
            </div>

            <div class="stat-grid">
                @foreach($sheet['attributes'] as $attribute)
                    <x-sheet.attribute-card :attribute="$attribute" />
                @endforeach
            </div>

            <div class="section">
                <div class="section-title">
                    <div>
                        <h2>Ce que tu sais faire</h2>
                        <p>Calculées à partir de tes caractéristiques, bonus du maître du jeu compris.</p>
                    </div>
                </div>

                @forelse($sheet['skills'] as $category => $skills)
                    <div class="skill-group">
                        <div class="skill-group-title"><span class="eyebrow">{{ $category }}</span></div>
                        <div class="skill-list">
                            @foreach($skills as $skill)
                                @include('components.sheet.skill-row', ['skill' => $skill, 'editableBonus' => true])
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="card empty">Aucune compétence consignée pour l’instant.</div>
                @endforelse
            </div>

            @if($sheet['affinities']->isNotEmpty())
                <a class="card section ally-card-link" href="{{ route('player.allies.show', $ally['id']) }}">
                    <header class="card-header"><h2>Affinités ressenties</h2><span class="badge badge-gold">Magie</span></header>
                    <div class="card-body">
                        @foreach($sheet['affinities'] as $affinity)
                            @include('components.sheet.affinity-row', ['affinity' => $affinity])
                        @endforeach
                    </div>
                </a>
            @endif
        </section>

        {{-- ── Maîtrises ───────────────────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="section-title">
                <div>
                    <h2>Maîtrises</h2>
                    <p>Les disciplines que tu as réellement travaillées.</p>
                </div>
            </div>

            @forelse($sheet['masteries'] as $category => $masteries)
                <div class="skill-group">
                    <div class="skill-group-title"><span class="eyebrow">{{ $category }}</span></div>
                    <div class="grid grid-2">
                        @foreach($masteries as $mastery)
                            @include('components.sheet.mastery-card', ['mastery' => $mastery])
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card empty">Tu n’as encore appris aucune discipline. Tout commence par un premier geste.</div>
            @endforelse

            @if($sheet['affinities']->isNotEmpty())
                <section class="card section">
                    <header class="card-header"><h2>Affinités magiques</h2></header>
                    <div class="card-body">
                        @foreach($sheet['affinities'] as $affinity)
                            @include('components.sheet.affinity-row', ['affinity' => $affinity])
                        @endforeach
                    </div>
                </section>
            @endif
        </section>

        {{-- ── Capacités ───────────────────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="section-title">
                <div>
                    <h2>Sorts et techniques</h2>
                    <p>Ouvre une capacité pour en relire le détail.</p>
                </div>
            </div>

            @forelse($sheet['abilities'] as $type => $abilities)
                <div class="skill-group">
                    <div class="skill-group-title"><span class="eyebrow">{{ $type }}</span></div>
                    <div class="stack">
                        @foreach($abilities as $ability)
                            @include('components.sheet.ability-card', ['ability' => $ability])
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card empty">Aucune capacité connue. Elles viendront avec la pratique.</div>
            @endforelse
        </section>

        {{-- ── Histoire ────────────────────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="grid grid-2">
                <section class="card">
                    <header class="card-header">
                        <h2>Qui tu es</h2>
                        <label class="btn btn-ghost btn-sm" for="edit-identity" title="Modifier">✎</label>
                    </header>
                    <div class="card-body list">
                        @foreach([
                            'Prénom' => $identity['first_name'],
                            'Nom' => $identity['last_name'],
                            'Surnom' => $identity['nickname'],
                            'Âge' => $identity['age_label'],
                            'Genre' => $identity['gender'],
                            'Race' => $identity['race'],
                            'Origine' => $identity['origin'],
                            'Lieu actuel' => $identity['current_location'],
                            'Statut' => $identity['occupation'],
                            'Titre' => $identity['adventurer_title'],
                        ] as $label => $value)
                            <div class="list-row">
                                <span class="muted small">{{ $label }}</span>
                                <strong class="small">{{ $value ?: '—' }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="stack">
                    <section class="card">
                        <header class="card-header">
                            <h2>Ton histoire</h2>
                            <div class="actions">
                                @if($identity['background'])<span class="badge badge-gold">{{ $identity['background'] }}</span>@endif
                                <label class="btn btn-ghost btn-sm" for="edit-story" title="Modifier">✎</label>
                            </div>
                        </header>
                        <div class="card-body">
                            <p class="muted" style="margin:0;line-height:1.75;white-space:pre-line">{{ $identity['biography'] ?: 'Ce chapitre reste encore à écrire.' }}</p>
                        </div>
                    </section>

                    @if($identity['traits'])
                        <section class="card">
                            <header class="card-header"><h2>Traits particuliers</h2></header>
                            <div class="card-body">
                                <p class="muted" style="margin:0;line-height:1.7;white-space:pre-line">{{ $identity['traits'] }}</p>
                            </div>
                        </section>
                    @endif
                </div>
            </div>

            @if($sheet['inventory']->isNotEmpty())
                <section class="card section">
                    <header class="card-header">
                        <div><h2>Ce que tu portes</h2><p class="muted small" style="margin:.2rem 0 0">Aperçu rapide de ton sac.</p></div>
                        <a class="btn btn-secondary btn-sm" href="{{ route('player.inventory') }}">Inventaire complet</a>
                    </header>
                    <div class="card-body list">
                        @foreach($sheet['inventory']->take(6) as $item)
                            <div class="list-row">
                                <div>
                                    <strong>{{ $item['name'] }}</strong>
                                    @if($item['equipped'])<span class="badge badge-gold">Équipé</span>@endif
                                    <div class="small muted">{{ $item['category'] }}</div>
                                </div>
                                <span class="badge">× {{ $item['quantity'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </section>

        {{-- ── Autres joueurs ──────────────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="section-title">
                <div>
                    <h2>Autres joueurs</h2>
                    <p>Ceux avec qui tu grandis. Tu vois leurs chiffres, pas leur sac ni leurs notes.</p>
                </div>
            </div>

            @forelse($allies as $ally)
                <section class="card section">
                    <header class="card-header">
                        <div class="actions">
                            <span class="brand-mark">
                                @if($ally['identity']['portrait_path'])
                                    <img src="{{ $ally['identity']['portrait_path'] }}" alt="">
                                @else
                                    {{ $ally['identity']['initials'] }}
                                @endif
                            </span>
                            <div>
                                <h3 style="margin:0">{{ $ally['identity']['name'] }}</h3>
                                <span class="eyebrow">
                                    joué par {{ $ally['player_name'] }}@if($ally['house']) · {{ $ally['house'] }}@endif
                                </span>
                            </div>
                        </div>
                        <div class="actions">
                            <span class="badge">{{ $ally['resources']['health'] }} / {{ $ally['resources']['max_health'] }} PV</span>
                            <span class="badge">{{ $ally['resources']['mana'] }} / {{ $ally['resources']['mana_max'] }} mana</span>
                        </div>
                    </header>

                    <div class="card-body">
                        <div class="stat-grid">
                            @foreach($ally['attributes'] as $attribute)
                                <div class="card stat-card">
                                    <span class="stat-abbr">{{ $attribute['abbreviation'] }}</span>
                                    <span class="stat-value">{{ $attribute['display'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        @php($topSkills = $ally['skills']->flatten(1)->sortByDesc('value')->take(6))
                        @if($topSkills->isNotEmpty())
                            <div class="skill-list section">
                                @foreach($topSkills as $skill)
                                    @include('components.sheet.skill-row', ['skill' => $skill])
                                @endforeach
                            </div>
                        @endif

                        @if($ally['states']->isNotEmpty())
                            <div class="actions">
                                @foreach($ally['states'] as $state)
                                    <span class="state-chip">
                                        @if($state['icon'])<span class="state-icon">{{ $state['icon'] }}</span>@endif
                                        {{ $state['name'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @empty
                <div class="card empty">Tu es seul à la table pour l’instant.</div>
            @endforelse
        </section>
    </div>
</div>

<div class="modal-overlay skill-bonus-modal" data-skill-modal role="dialog" aria-modal="true" aria-labelledby="skill-modal-title">
    <button class="modal-backdrop" type="button" data-skill-close aria-label="Fermer"></button>
    <div class="modal-panel card">
        <header class="card-header"><div><div class="eyebrow">Ajustement personnel</div><h2 id="skill-modal-title" data-skill-name></h2></div><button class="btn btn-ghost btn-sm" type="button" data-skill-close>✕</button></header>
        <form class="card-body" data-skill-form>
            <p class="muted">La valeur de base est calculée depuis tes caractéristiques. Tu peux uniquement modifier ton bonus personnel.</p>
            <div class="skill-calculation"><span>Base <strong data-skill-base></strong></span><span>Bonus MJ <strong data-skill-gm></strong></span><span>Mon bonus <strong data-skill-player-label></strong></span><span>Total <strong data-skill-total></strong></span></div>
            <div class="form-group"><label for="player-bonus">Mon bonus</label><input class="input" id="player-bonus" type="number" min="-50" max="50" name="player_bonus" required data-skill-player></div>
            <div class="actions" style="justify-content:flex-end;margin-top:1rem"><span class="small muted" data-skill-status></span><button class="btn btn-primary" type="submit">Enregistrer</button></div>
        </form>
    </div>
</div>

{{-- ── Modales d'édition ───────────────────────────────────────────────── --}}
<input class="modal-toggle" type="checkbox" id="edit-identity" hidden>
<div class="modal-overlay" role="dialog" aria-modal="true" aria-label="Modifier ton identité">
    <label class="modal-backdrop" for="edit-identity" aria-hidden="true"></label>
    <div class="modal-panel card modal-panel-wide">
        <header class="card-header">
            <h2>Qui tu es</h2>
            <label class="btn btn-ghost btn-sm" for="edit-identity" title="Fermer">✕</label>
        </header>

        {{-- Portrait : formulaire séparé, car il transporte un fichier. --}}
        <form method="POST" action="{{ route('player.portrait.update') }}" enctype="multipart/form-data" class="card-body">
            @csrf
            <div class="actions">
                <span class="brand-mark">
                    @if($identity['portrait_path'])
                        <img src="{{ $identity['portrait_path'] }}" alt="">
                    @else
                        {{ $identity['initials'] }}
                    @endif
                </span>
                <div class="form-group" style="flex:1;min-width:12rem">
                    <label for="portrait">Portrait (JPEG, PNG ou WebP — 4 Mo max)</label>
                    <input class="input" id="portrait" type="file" name="portrait" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <button class="btn btn-secondary btn-sm" type="submit">Envoyer</button>
            </div>
            @error('portrait')<p class="form-error">{{ $message }}</p>@enderror
        </form>

        @if($identity['portrait_path'])
            <form method="POST" action="{{ route('player.portrait.destroy') }}" class="card-body" style="padding-top:0">
                @csrf @method('DELETE')
                <button class="btn btn-ghost btn-sm" type="submit">Retirer le portrait</button>
            </form>
        @endif

        <form method="POST" action="{{ route('player.identity.update') }}" class="card-body" style="padding-top:0">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="form-group"><label>Prénom</label><input class="input" name="first_name" value="{{ old('first_name', $identity['first_name']) }}" required></div>
                <div class="form-group"><label>Nom</label><input class="input" name="last_name" value="{{ old('last_name', $identity['last_name']) }}"></div>
                <div class="form-group"><label>Surnom</label><input class="input" name="nickname" value="{{ old('nickname', $identity['nickname']) }}"></div>
                <div class="form-group"><label>Genre</label><input class="input" name="gender" value="{{ old('gender', $identity['gender']) }}"></div>
                <div class="form-group"><label>Race</label><input class="input" name="ancestry" value="{{ old('ancestry', $identity['race']) }}"></div>
                <div class="form-group"><label>Lieu actuel</label><input class="input" name="current_location" value="{{ old('current_location', $identity['current_location']) }}"></div>
                <div class="form-group"><label>Statut</label><input class="input" name="occupation" value="{{ old('occupation', $identity['occupation']) }}"></div>
                <div class="form-group"><label>Titre</label><input class="input" name="adventurer_title" value="{{ old('adventurer_title', $identity['adventurer_title']) }}"></div>
            </div>
            {{-- Histoire et traits voyagent avec l'identité : un seul enregistrement. --}}
            <input type="hidden" name="background" value="{{ $identity['background'] }}">
            <input type="hidden" name="biography" value="{{ $identity['biography'] }}">
            <input type="hidden" name="traits" value="{{ $identity['traits'] }}">
            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.8rem">Enregistrer</button>
        </form>
    </div>
</div>

<input class="modal-toggle" type="checkbox" id="edit-story" hidden>
<div class="modal-overlay" role="dialog" aria-modal="true" aria-label="Modifier ton histoire">
    <label class="modal-backdrop" for="edit-story" aria-hidden="true"></label>
    <div class="modal-panel card modal-panel-wide">
        <header class="card-header">
            <h2>Ton histoire</h2>
            <label class="btn btn-ghost btn-sm" for="edit-story" title="Fermer">✕</label>
        </header>
        <form method="POST" action="{{ route('player.identity.update') }}" class="card-body">
            @csrf @method('PUT')
            <div class="form-group"><label>Historique en une ligne</label><input class="input" name="background" value="{{ old('background', $identity['background']) }}" placeholder="Enfant de fermiers"></div>
            <div class="form-group"><label>Biographie</label><textarea class="textarea" name="biography" rows="9">{{ old('biography', $identity['biography']) }}</textarea></div>
            <div class="form-group"><label>Traits particuliers</label><textarea class="textarea" name="traits" rows="4">{{ old('traits', $identity['traits']) }}</textarea></div>

            {{-- L'identité voyage avec l'histoire, pour la même raison. --}}
            <input type="hidden" name="first_name" value="{{ $identity['first_name'] }}">
            <input type="hidden" name="last_name" value="{{ $identity['last_name'] }}">
            <input type="hidden" name="nickname" value="{{ $identity['nickname'] }}">
            <input type="hidden" name="gender" value="{{ $identity['gender'] }}">
            <input type="hidden" name="ancestry" value="{{ $identity['race'] }}">
            <input type="hidden" name="current_location" value="{{ $identity['current_location'] }}">
            <input type="hidden" name="occupation" value="{{ $identity['occupation'] }}">
            <input type="hidden" name="adventurer_title" value="{{ $identity['adventurer_title'] }}">

            <button class="btn btn-primary btn-sm" type="submit">Enregistrer</button>
        </form>
    </div>
</div>
@endsection
