@extends('layouts.app')

{{--
    Fiche personnage — vue MJ.

    Volontairement construite sur la même trame visuelle que la fiche joueur
    (même en-tête, mêmes cartes de caractéristiques, mêmes cartes de maîtrise)
    pour que le MJ retrouve instantanément ses repères, mais enrichie des
    données internes : notes MJ, objets cachés, et un sélecteur de visibilité
    sur chaque compétence, maîtrise, affinité et capacité.
--}}

@section('title', 'Fiche de '.$character->displayName())
@section('context', 'Table du maître du jeu')

@section('content')
@php($identity = $sheet['identity'])
@php($resources = $sheet['resources'])

<div class="page-heading">
    <div class="eyebrow"><a href="{{ route('gm.dashboard') }}">← Retour à la tablée</a></div>
    <h1>{{ $identity['name'] }}</h1>
    <p>Joué par {{ $character->user->name }}. Toute modification est immédiatement visible sur son espace.</p>
</div>

{{-- En-tête miroir de la fiche joueur --}}
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
        <h1 style="font-size:clamp(1.5rem,3vw,2rem)">{{ $identity['name'] }}</h1>
        <p class="sheet-traits">
            <span>{{ $identity['age_label'] }}</span>
            @if($identity['race'])<span>{{ $identity['race'] }}</span>@endif
            @if($identity['origin'])<span>{{ $identity['origin'] }}</span>@endif
            <span>{{ $identity['status'] }}</span>
        </p>

        <x-sheet.resource-sliders :resources="$resources" :character-id="$character->id" />

        <div class="actions" style="margin-top:.9rem">
            @foreach($sheet['states'] as $state)
                <span class="state-chip {{ $state['visible_to_player'] ? '' : 'is-hidden-from-player' }}">
                    @if($state['icon'])<span class="state-icon">{{ $state['icon'] }}</span>@endif
                    {{ $state['name'] }}
                    @if($state['modifier_summary'])<span class="muted small">· {{ $state['modifier_summary'] }}</span>@endif
                    @unless($state['visible_to_player'])<span class="muted small">· caché</span>@endunless
                </span>
            @endforeach
        </div>
    </div>
</section>

<div class="gm-character-sheet">
    <input class="sheet-tabs" type="radio" name="gm-tab" id="gmtab-stats" checked>
    <input class="sheet-tabs" type="radio" name="gm-tab" id="gmtab-skills">
    <input class="sheet-tabs" type="radio" name="gm-tab" id="gmtab-masteries">
    <input class="sheet-tabs" type="radio" name="gm-tab" id="gmtab-abilities">
    <input class="sheet-tabs" type="radio" name="gm-tab" id="gmtab-identity">

    <nav class="tab-bar" aria-label="Sections de la fiche">
        <label for="gmtab-stats">Caractéristiques</label>
        <label for="gmtab-skills">Compétences</label>
        <label for="gmtab-masteries">Maîtrises &amp; affinités</label>
        <label for="gmtab-abilities">Capacités &amp; états</label>
        <label for="gmtab-identity">Identité &amp; ressources</label>
    </nav>

    <div class="sheet-panels">

        {{-- ── Identité & ressources ───────────────────────────────────── --}}
        <section class="sheet-panel">
            <section class="card" style="margin-bottom:1rem">
                <header class="card-header"><div><h2>Portrait du personnage</h2><p class="muted small" style="margin:.2rem 0 0">JPEG, PNG ou WebP — 4 Mo maximum.</p></div></header>
                <form method="POST" action="{{ route('gm.characters.portrait.update', $character) }}" enctype="multipart/form-data" class="card-body gm-inline-form">
                    @csrf
                    <div class="form-group" style="flex:1;min-width:14rem"><label for="character-portrait">Fichier</label><input class="input" id="character-portrait" type="file" name="portrait" accept="image/jpeg,image/png,image/webp" required></div>
                    <button class="btn btn-secondary btn-sm" type="submit">Mettre à jour</button>
                </form>
                @if($identity['portrait_path'])
                    <form method="POST" action="{{ route('gm.characters.portrait.destroy', $character) }}" class="card-body" style="padding-top:0">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm" type="submit">Retirer le portrait</button>
                    </form>
                @endif
            </section>
            <section class="card">
                <header class="card-header">
                    <div><h2>Identité et ressources</h2><p class="muted small" style="margin:.2rem 0 0">Un personnage peut n’avoir ni classe ni profession : tout est facultatif.</p></div>
                </header>
                <form method="POST" action="{{ route('gm.characters.update', $character) }}" class="card-body">
                    @csrf @method('PUT')
                    <div class="form-grid">
                        <div class="form-group"><label>Prénom</label><input class="input" name="first_name" value="{{ old('first_name', $character->first_name ?: $character->name) }}" required></div>
                        <div class="form-group"><label>Nom</label><input class="input" name="last_name" value="{{ old('last_name', $character->last_name) }}"></div>
                        <div class="form-group"><label>Surnom</label><input class="input" name="nickname" value="{{ old('nickname', $character->nickname) }}"></div>
                        <div class="form-group"><label>Portrait (URL ou chemin)</label><input class="input" name="portrait_path" value="{{ old('portrait_path', $character->portrait_path) }}"></div>
                        <div class="form-group"><label>Âge (années)</label><input class="input" type="number" min="0" name="age_years" value="{{ old('age_years', $character->age_years) }}" required></div>
                        <div class="form-group"><label>Date de naissance</label><input class="input" type="date" name="birth_date" value="{{ old('birth_date', $character->birth_date?->format('Y-m-d')) }}"></div>
                        <div class="form-group"><label>Genre</label><input class="input" name="gender" value="{{ old('gender', $character->gender) }}"></div>
                        <div class="form-group"><label>Race</label><input class="input" name="ancestry" value="{{ old('ancestry', $character->ancestry) }}"></div>
                        <div class="form-group"><label>Origine</label><input class="input" name="origin" value="{{ old('origin', $character->origin) }}"></div>
                        <div class="form-group"><label>Lieu actuel</label><input class="input" name="current_location" value="{{ old('current_location', $character->current_location) }}"></div>
                        <div class="form-group"><label>Profession / statut</label><input class="input" name="occupation" value="{{ old('occupation', $character->occupation) }}"></div>
                        <div class="form-group"><label>Classe / archétype</label><input class="input" name="archetype" value="{{ old('archetype', $character->archetype) }}" placeholder="Laisser vide tant qu’aucune n’émerge"></div>
                        <div class="form-group"><label>Titre d’aventurier</label><input class="input" name="adventurer_title" value="{{ old('adventurer_title', $character->adventurer_title) }}"></div>
                        <div class="form-group"><label>Historique</label><input class="input" name="background" value="{{ old('background', $character->background) }}"></div>

                        <div class="form-group"><label>PV actuels</label><input class="input" type="number" min="0" name="health" value="{{ old('health', $character->health) }}" required></div>
                        <div class="form-group"><label>PV maximum</label><input class="input" type="number" min="1" name="max_health" value="{{ old('max_health', $character->max_health) }}" required></div>
                        <div class="form-group"><label>Mana actuel</label><input class="input" type="number" min="0" name="mana_current" value="{{ old('mana_current', $character->mana_current) }}" required></div>
                        <div class="form-group">
                            <label>Mana maximum</label>
                            <input class="input" type="number" min="0" name="mana_max" value="{{ old('mana_max', $character->mana_max) }}" placeholder="Auto : {{ $resources['mana_max'] }}">
                            <span class="muted small">Vide = dérivé de MAN par la formule.</span>
                        </div>
                        <div class="form-group"><label>Armure</label><input class="input" type="number" min="0" name="armor" value="{{ old('armor', $character->armor) }}" required></div>
                        <div class="form-group"><label>Pièces d’or</label><input class="input" type="number" min="0" name="gold" value="{{ old('gold', $character->gold) }}" required></div>
                        <div class="form-group"><label>État général</label><input class="input" name="status" value="{{ old('status', $character->status) }}" required></div>
                        <div class="form-group"><label>Zone actuelle</label>
                            <select class="select" name="current_map_id">
                                <option value="">Inconnue</option>
                                @foreach($maps as $map)<option value="{{ $map->id }}" @selected(old('current_map_id', $character->current_map_id) == $map->id)>{{ $map->title }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group full"><label>Biographie</label><textarea class="textarea" name="biography" rows="4">{{ old('biography', $character->biography) }}</textarea></div>
                        <div class="form-group full"><label>Traits particuliers</label><textarea class="textarea" name="traits" rows="3">{{ old('traits', $character->traits) }}</textarea></div>
                        <div class="form-group full actions"><button class="btn btn-primary" type="submit">Enregistrer la fiche</button></div>
                    </div>
                </form>
            </section>

            <section class="card section">
                <header class="card-header"><div><h2>Catalogue</h2><p class="muted small" style="margin:.2rem 0 0">Après l’ajout d’une compétence ou d’une école de magie à la campagne.</p></div></header>
                <form method="POST" action="{{ route('gm.characters.synchronise', $character) }}" class="card-body">
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit">Synchroniser la fiche avec le catalogue</button>
                </form>
            </section>
        </section>

        {{-- ── Caractéristiques ────────────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="section-title">
                <div><h2>Caractéristiques</h2><p>Valeurs principales et modificateurs appliqués au personnage.</p></div>
            </div>

            <div class="stat-grid">
                @foreach($sheet['attributes'] as $attribute)
                    <x-sheet.attribute-card :attribute="$attribute" :editable="true" :character-id="$character->id" />
                @endforeach
            </div>
        </section>

        {{-- ── Compétences ─────────────────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="section-title">
                <div><h2>Compétences</h2><p>Valeur calculée depuis les caractéristiques, plus un éventuel bonus personnel.</p></div>
            </div>

            @foreach($sheet['skills'] as $category => $skills)
                <div class="skill-group">
                    <div class="skill-group-title"><span class="eyebrow">{{ $category }}</span></div>
                    <div class="skill-list">
                        @foreach($skills as $skill)
                            @include('components.sheet.skill-row', ['skill' => $skill, 'editableBonus' => 'gm', 'characterId' => $character->id])
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>

        {{-- ── Maîtrises & affinités ───────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="section-title"><div><h2>Maîtrises</h2><p>Caractéristiques → compétences → maîtrises → techniques.</p></div></div>

            @forelse($sheet['masteries'] as $category => $masteries)
                <div class="skill-group">
                    <div class="skill-group-title"><span class="eyebrow">{{ $category }}</span></div>
                    <div class="grid grid-2">
                        @foreach($masteries as $mastery)
                            <section class="card mastery-card">
                                <div class="mastery-head">
                                    <strong>{{ $mastery['name'] }}</strong>
                                    <span class="rank-pill">{{ $mastery['rank_label'] }}</span>
                                </div>
                                <div class="rank-track">
                                    @foreach($masteryRanks as $index => $rank)
                                        <span class="rank-step {{ $index <= $mastery['rank_index'] ? 'is-filled' : '' }}" title="{{ $rank }}"></span>
                                    @endforeach
                                </div>
                                @if($mastery['gm_notes'])<p class="muted small" style="margin:0">{{ $mastery['gm_notes'] }}</p>@endif

                                <div class="actions" style="justify-content:space-between">
                                    @include('components.sheet.reveal-switch', ['type' => 'mastery', 'id' => $mastery['id'], 'current' => $mastery['reveal_state']])
                                </div>

                                <form method="POST" action="{{ route('gm.masteries.update', [$character, $mastery['id']]) }}">
                                    @csrf @method('PUT')
                                    <div class="gm-inline-form">
                                        <div class="form-group"><label>Rang</label>
                                            <select class="select input-sm" name="rank_index">
                                                @foreach($masteryRanks as $index => $rank)<option value="{{ $index }}" @selected($index === $mastery['rank_index'])>{{ $rank }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div class="form-group"><label>Prog. %</label><input class="input input-xs" type="number" min="0" max="100" name="progress" value="{{ $mastery['progress'] }}" required></div>
                                        
                                        <input type="hidden" name="reveal_state" value="{{ $mastery['reveal_state'] }}">
                                        <input type="hidden" name="gm_notes" value="{{ $mastery['gm_notes'] }}">
                                        <button class="btn btn-secondary btn-sm" type="submit">OK</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('gm.masteries.destroy', [$character, $mastery['id']]) }}" onsubmit="return confirm('Retirer cette maîtrise ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">Retirer</button>
                                </form>
                            </section>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card empty">Aucune maîtrise. Ajoutez-en une ci-dessous.</div>
            @endforelse

            @if($masteryCatalog->isNotEmpty())
                <section class="card section">
                    <header class="card-header"><h2>Accorder une maîtrise</h2></header>
                    <form method="POST" action="{{ route('gm.masteries.store', $character) }}" class="card-body">
                        @csrf
                        <div class="gm-inline-form">
                            <div class="form-group" style="flex:1;min-width:12rem"><label>Maîtrise</label>
                                <select class="select" name="mastery_definition_id" required>
                                    @foreach($masteryCatalog as $definition)<option value="{{ $definition->id }}">{{ $definition->name }} · {{ $definition->category->label() }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group"><label>Rang</label>
                                <select class="select input-sm" name="rank_index">
                                    @foreach($masteryRanks as $index => $rank)<option value="{{ $index }}">{{ $rank }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group"><label>Prog. %</label><input class="input input-xs" type="number" min="0" max="100" name="progress" value="0" required></div>
                            
                            <div class="form-group"><label>Visibilité</label>
                                <select class="select input-sm" name="reveal_state">
                                    <option value="hidden">Cachée</option>
                                    <option value="approximate">Approximative</option>
                                    <option value="revealed">Révélée</option>
                                </select>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit">Accorder</button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="card section">
                <header class="card-header"><div><h2>Affinités magiques</h2><p class="muted small" style="margin:.2rem 0 0">Indépendantes des maîtrises : un potentiel peut exister sans entraînement.</p></div></header>
                <div class="card-body stack">
                    @foreach($sheet['affinities'] as $affinity)
                        <div class="card card-body" style="padding:.7rem .9rem">
                            {{-- Le sélecteur de visibilité poste sur sa propre route : il est
                                 gardé hors du formulaire d'édition (pas de formulaires imbriqués). --}}
                            <div class="gm-inline-form" style="justify-content:space-between">
                                <strong class="small">{{ $affinity['school'] }} — {{ $affinity['level_label'] }}</strong>
                                @include('components.sheet.reveal-switch', ['type' => 'affinity', 'id' => $affinity['id'], 'current' => $affinity['reveal_state']])
                            </div>
                            <form method="POST" action="{{ route('gm.affinities.update', [$character, $affinity['id']]) }}" style="margin-top:.5rem">
                                @csrf @method('PUT')
                                <div class="gm-inline-form">
                                    <div class="form-group"><label>Niveau</label>
                                        <select class="select input-sm" name="affinity_level">
                                            @foreach($affinityLevels as $index => $level)<option value="{{ $index }}" @selected($index === $affinity['level'])>{{ $level }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="form-group" style="flex:1;min-width:11rem"><label>Note MJ</label><input class="input" type="text" name="gm_notes" value="{{ $affinity['gm_notes'] }}"></div>
                                    <input type="hidden" name="reveal_state" value="{{ $affinity['reveal_state'] }}">
                                    <button class="btn btn-secondary btn-sm" type="submit">OK</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>
        </section>

        {{-- ── Capacités & états ───────────────────────────────────────── --}}
        <section class="sheet-panel">
            <div class="section-title"><div><h2>Capacités</h2><p>Sorts, techniques et talents. Une capacité peut être acquise sans être connue du joueur.</p></div></div>

            <div class="stack">
                @forelse($sheet['abilities']->flatten(1) as $ability)
                    <section class="card card-body" style="padding:.8rem .95rem">
                        <div class="actions" style="justify-content:space-between">
                            <div>
                                <strong>{{ $ability['name'] }}</strong>
                                <div class="small muted">
                                    {{ $ability['type_label'] }}@if($ability['mastery']) · {{ $ability['mastery'] }}@endif
                                    @if($ability['mana_cost']) · {{ $ability['mana_cost'] }} mana @endif
                                    @if($ability['minimum_rank']) · {{ $ability['minimum_rank'] }} @endif
                                </div>
                                @if($ability['gm_notes'])<div class="small gold" style="margin-top:.3rem">{{ $ability['gm_notes'] }}</div>@endif
                            </div>
                            <div class="actions">
                                <span class="badge {{ $ability['unlocked'] ? 'badge-green' : '' }}">{{ $ability['unlocked'] ? 'Acquise' : 'Verrouillée' }}</span>
                                @include('components.sheet.reveal-switch', ['type' => 'ability', 'id' => $ability['id'], 'current' => $ability['reveal_state']])
                            </div>
                        </div>

                        <div class="actions" style="margin-top:.5rem">
                            <form method="POST" action="{{ route('gm.abilities.update', [$character, $ability['id']]) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="reveal_state" value="{{ $ability['reveal_state'] }}">
                                <input type="hidden" name="gm_notes" value="{{ $ability['gm_notes'] }}">
                                @unless($ability['unlocked'])<input type="hidden" name="unlocked" value="1">@endunless
                                <button class="btn btn-secondary btn-sm" type="submit">{{ $ability['unlocked'] ? 'Verrouiller' : 'Débloquer' }}</button>
                            </form>
                            <form method="POST" action="{{ route('gm.abilities.destroy', [$character, $ability['id']]) }}" onsubmit="return confirm('Retirer cette capacité ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Retirer</button>
                            </form>
                        </div>
                    </section>
                @empty
                    <div class="card empty">Aucune capacité accordée.</div>
                @endforelse
            </div>

            @if($abilityCatalog->isNotEmpty())
                <section class="card section">
                    <header class="card-header"><h2>Accorder une capacité</h2></header>
                    <form method="POST" action="{{ route('gm.abilities.store', $character) }}" class="card-body">
                        @csrf
                        <div class="gm-inline-form">
                            <div class="form-group" style="flex:1;min-width:14rem"><label>Capacité</label>
                                <select class="select" name="ability_definition_id" required>
                                    @foreach($abilityCatalog as $definition)<option value="{{ $definition->id }}">{{ $definition->name }} · {{ $definition->type->label() }}</option>@endforeach
                                </select>
                            </div>
                            <label class="check" style="min-height:2.2rem"><input type="checkbox" name="unlocked" value="1" checked> <span class="small">Acquise</span></label>
                            <div class="form-group"><label>Visibilité</label>
                                <select class="select input-sm" name="reveal_state">
                                    <option value="hidden">Cachée</option>
                                    <option value="revealed">Révélée</option>
                                </select>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit">Accorder</button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="card section">
                <header class="card-header"><div><h2>États</h2><p class="muted small" style="margin:.2rem 0 0">Un état peut agir sans que le joueur en soit informé.</p></div></header>
                <div class="card-body stack">
                    @foreach($sheet['states'] as $state)
                        <div class="card card-body" style="padding:.7rem .9rem">
                            <div class="actions" style="justify-content:space-between">
                                <div>
                                    <strong>@if($state['icon']){{ $state['icon'] }} @endif{{ $state['name'] }}</strong>
                                    <div class="small muted">{{ $state['description'] }}
                                        @if($state['modifier_summary']) · {{ $state['modifier_summary'] }}@endif
                                        @if($state['duration_label']) · {{ $state['duration_label'] }}@endif
                                    </div>
                                </div>
                                <div class="actions">
                                    <span class="badge {{ $state['is_active'] ? 'badge-green' : '' }}">{{ $state['is_active'] ? 'Actif' : 'Inactif' }}</span>
                                    <span class="badge {{ $state['visible_to_player'] ? 'badge-gold' : '' }}">{{ $state['visible_to_player'] ? 'Visible' : 'Caché' }}</span>
                                    <form method="POST" action="{{ route('gm.states.destroy', [$character, $state['id']]) }}" onsubmit="return confirm('Retirer cet état ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">Retirer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <form method="POST" action="{{ route('gm.states.store', $character) }}">
                        @csrf
                        <div class="gm-inline-form">
                            <div class="form-group"><label>Nom</label>
                                <input class="input input-sm" name="name" list="state-presets" placeholder="Blessé" required>
                                <datalist id="state-presets">
                                    @foreach($statePresets as $preset)<option value="{{ $preset['name'] }}">@endforeach
                                </datalist>
                            </div>
                            <div class="form-group"><label>Icône</label><input class="input input-xs" name="icon" maxlength="8" placeholder="♦"></div>
                            <div class="form-group" style="flex:1;min-width:11rem"><label>Description</label><input class="input" name="description"></div>
                            <div class="form-group"><label>Durée</label><input class="input input-sm" name="duration_label" placeholder="2 jours"></div>
                            @foreach($sheet['attributes'] as $attribute)
                                <div class="form-group"><label>{{ $attribute['abbreviation'] }}</label><input class="input input-xs" type="number" name="modifiers[{{ $attribute['code'] }}]" placeholder="0"></div>
                            @endforeach
                            <label class="check" style="min-height:2.2rem"><input type="checkbox" name="visible_to_player" value="1" checked> <span class="small">Visible</span></label>
                            <input type="hidden" name="is_active" value="1">
                            <button class="btn btn-primary btn-sm" type="submit">Appliquer</button>
                        </div>
                    </form>
                </div>
            </section>
        </section>

    </div>
</div>

<div class="modal-overlay" data-attribute-modal role="dialog" aria-modal="true" aria-labelledby="attribute-modal-title">
    <button class="modal-backdrop" type="button" data-attribute-close aria-label="Fermer"></button>
    <div class="modal-panel card">
        <header class="card-header"><div><div class="eyebrow">Caractéristique principale</div><h2 id="attribute-modal-title" data-attribute-name></h2></div><button class="btn btn-ghost btn-sm" type="button" data-attribute-close>✕</button></header>
        <form class="card-body" data-attribute-form>
            <div class="form-grid">
                <div class="form-group"><label for="attribute-value">Valeur principale</label><input class="input" id="attribute-value" type="number" min="0" max="{{ config('jdr.character.attribute_max') }}" required data-attribute-value></div>
                <div class="form-group"><label for="attribute-modifier">Modificateur</label><input class="input" id="attribute-modifier" type="number" min="-20" max="20" required data-attribute-modifier-input></div>
            </div>
            <div class="actions" style="justify-content:flex-end;margin-top:1rem"><span class="small muted" data-attribute-status></span><button class="btn btn-primary" type="submit">Enregistrer</button></div>
        </form>
    </div>
</div>

<div class="modal-overlay skill-bonus-modal" data-skill-modal role="dialog" aria-modal="true" aria-labelledby="skill-modal-title">
    <button class="modal-backdrop" type="button" data-skill-close aria-label="Fermer"></button>
    <div class="modal-panel card">
        <header class="card-header"><div><div class="eyebrow">Gestion de la compétence</div><h2 id="skill-modal-title" data-skill-name></h2></div><button class="btn btn-ghost btn-sm" type="button" data-skill-close>✕</button></header>
        <form class="card-body" data-skill-form>
            <div class="skill-calculation"><span>Base <strong data-skill-base></strong></span><span>Bonus MJ <strong data-skill-gm></strong></span><span>Bonus joueur <strong data-skill-player-label></strong></span><span>Total <strong data-skill-total></strong></span></div>
            <div class="form-grid">
                <div class="form-group"><label for="gm-bonus">Bonus MJ</label><input class="input" id="gm-bonus" type="number" min="-50" max="50" required data-skill-gm-input></div>
                <div class="form-group"><label for="gm-player-bonus">Bonus joueur</label><input class="input" id="gm-player-bonus" type="number" min="-50" max="50" required data-skill-player></div>
                <div class="form-group"><label for="gm-skill-state">Visibilité</label><select class="select" id="gm-skill-state" data-skill-reveal><option value="hidden">Cachée</option><option value="revealed">Révélée</option></select></div>
                <div class="form-group full"><label for="gm-skill-notes">Note MJ</label><textarea class="textarea" id="gm-skill-notes" rows="3" maxlength="2000" data-skill-notes></textarea></div>
            </div>
            <div class="actions" style="justify-content:flex-end;margin-top:1rem"><span class="small muted" data-skill-status></span><button class="btn btn-primary" type="submit">Enregistrer</button></div>
        </form>
    </div>
</div>

<section class="card section">
    <header class="card-header"><h2>Inventaire</h2><span class="badge">{{ $character->inventoryItems->sum('quantity') }} objet(s)</span></header>
    <div class="card-body stack">
        @foreach($character->inventoryItems as $item)
            <details class="card details-form">
                <summary class="card-body">
                    <div class="actions" style="justify-content:space-between">
                        <div><strong>{{ $item->name }}</strong><div class="small muted">{{ $item->category }} · {{ $item->quantity }} × </div></div>
                        @if($item->equipped)<span class="badge badge-gold">Équipé</span>@endif
                    </div>
                </summary>
                <form method="POST" action="{{ route('gm.inventory.update', [$character, $item]) }}" class="card-body" style="padding-top:0">
                    @csrf @method('PUT')
                    <div class="form-grid">
                        <div class="form-group"><label>Nom</label><input class="input" name="name" value="{{ $item->name }}" required></div>
                        <div class="form-group"><label>Catégorie</label><input class="input" name="category" value="{{ $item->category }}" required></div>
                        <div class="form-group"><label>Quantité</label><input class="input" name="quantity" type="number" min="1" value="{{ $item->quantity }}" required></div>
                        <div class="form-group full"><label>Description</label><textarea class="textarea" name="description">{{ $item->description }}</textarea></div>
                        <label class="check"><input type="checkbox" name="equipped" value="1" @checked($item->equipped)> Équipé</label>
                        <label class="check"><input type="checkbox" name="is_visible_to_player" value="1" @checked($item->is_visible_to_player)> Visible du joueur</label>
                    </div>
                    <div class="actions" style="margin-top:.7rem"><button class="btn btn-secondary btn-sm" type="submit">Enregistrer</button></div>
                </form>
                <form method="POST" action="{{ route('gm.inventory.destroy', [$character, $item]) }}" class="card-body" style="padding-top:0" onsubmit="return confirm('Retirer cet objet ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit">Retirer</button>
                </form>
            </details>
        @endforeach

        <details class="card details-form">
            <summary class="card-body"><strong class="gold">＋ Glisser un objet dans la besace</strong></summary>
            <form method="POST" action="{{ route('gm.inventory.store', $character) }}" class="card-body">
                @csrf
                <div class="form-grid">
                    <div class="form-group"><label>Nom</label><input class="input" name="name" required></div>
                    <div class="form-group"><label>Catégorie</label><input class="input" name="category" value="Divers" required></div>
                    <div class="form-group"><label>Quantité</label><input class="input" name="quantity" type="number" min="1" value="1" required></div>
                    
                    <div class="form-group full"><label>Description</label><textarea class="textarea" name="description"></textarea></div>
                    <label class="check"><input type="checkbox" name="equipped" value="1"> Équipé</label>
                    <label class="check"><input type="checkbox" name="is_visible_to_player" value="1" checked> Visible du joueur</label>
                </div>
                <button class="btn btn-primary" style="margin-top:.8rem" type="submit">Ajouter</button>
            </form>
        </details>
    </div>
</section>
@endsection
