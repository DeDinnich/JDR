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

        <div class="resource-bars">
            <div class="resource">
                <div class="resource-label">
                    <span class="eyebrow">Points de vie</span>
                    <span class="resource-value">{{ $resources['health'] }} / {{ $resources['max_health'] }}</span>
                </div>
                <div class="gauge gauge-health"><span style="width:{{ $resources['health_percentage'] }}%"></span></div>
            </div>
            <div class="resource">
                <div class="resource-label">
                    <span class="eyebrow">Mana</span>
                    <span class="resource-value">{{ $resources['mana'] }} / {{ $resources['mana_max'] }}</span>
                </div>
                <div class="gauge gauge-mana"><span style="width:{{ $resources['mana_percentage'] }}%"></span></div>
            </div>
        </div>

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

    <nav class="tab-bar" aria-label="Sections de la fiche">
        <label for="tab-overview">Compétences</label>
        <label for="tab-masteries">Maîtrises</label>
        <label for="tab-abilities">Capacités</label>
        <label for="tab-story">Histoire</label>
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
                    <div class="card stat-card"
                         @if($attribute['description']) title="{{ $attribute['description'] }}" @endif>
                        <span class="stat-abbr">{{ $attribute['abbreviation'] }}</span>
                        <span class="stat-name">{{ $attribute['name'] }}</span>
                        <span class="stat-value">{{ $attribute['display'] }}</span>
                        @if($attribute['modifier'] !== 0)
                            <span class="stat-sub">{{ $attribute['modifier'] > 0 ? '+' : '' }}{{ $attribute['modifier'] }} d’un état en cours</span>
                        @endif
                    </div>
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
                                @include('components.sheet.skill-row', ['skill' => $skill])
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="card empty">Aucune compétence consignée pour l’instant.</div>
                @endforelse
            </div>

            @if($sheet['affinities']->isNotEmpty())
                <section class="card section">
                    <header class="card-header"><h2>Affinités ressenties</h2><span class="badge badge-gold">Magie</span></header>
                    <div class="card-body">
                        @foreach($sheet['affinities'] as $affinity)
                            @include('components.sheet.affinity-row', ['affinity' => $affinity])
                        @endforeach
                    </div>
                </section>
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
                    <header class="card-header"><h2>Qui tu es</h2></header>
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
                        <header class="card-header"><h2>Ton histoire</h2>
                            @if($identity['background'])<span class="badge badge-gold">{{ $identity['background'] }}</span>@endif
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
    </div>
</div>
@endsection
