@extends('layouts.app')

{{--
    Inventaire joueur.

    $items ne contient que les objets visibles : un objet glissé secrètement par
    le MJ n'arrive jamais jusqu'ici, et n'est donc ni affiché ni modifiable.
--}}

@section('title', 'Inventaire')
@section('content')
<div class="page-heading">
    <div class="eyebrow">Possessions de {{ $character->name }}</div>
    <h1>Inventaire</h1>
    <p>{{ $items->sum('quantity') }} objets · charge totale {{ number_format($totalWeight, 2, ',', ' ') }} kg</p>
</div>

<div class="grid grid-3">
    <div class="card metric"><div class="eyebrow">Objets équipés</div><div class="metric-value">{{ $items->where('equipped', true)->count() }}</div><div class="metric-note">Prêts à l’emploi</div></div>
    <div class="card metric"><div class="eyebrow">Charge</div><div class="metric-value">{{ number_format($totalWeight, 1, ',', ' ') }} kg</div><div class="metric-note">Poids transporté</div></div>

    {{-- La bourse s'ajuste sur place : c'est le chiffre qui bouge le plus en partie. --}}
    <form class="card metric" method="POST" action="{{ route('player.resources.update') }}">
        @csrf @method('PUT')
        <div class="eyebrow">Bourse</div>
        <div class="actions" style="justify-content:center;gap:.4rem">
            <input class="input input-xs" type="number" min="0" name="gold" value="{{ $character->gold }}"
                   style="max-width:7rem;text-align:center" aria-label="Pièces d’or" required>
            <span class="metric-note">po</span>
            <button class="btn btn-secondary btn-sm" type="submit">OK</button>
        </div>
        {{-- Les autres ressources sont reprises telles quelles : la règle de
             validation les exige, mais cet écran ne sert qu'à la bourse. --}}
        <input type="hidden" name="health" value="{{ $character->health }}">
        <input type="hidden" name="max_health" value="{{ $character->max_health }}">
        <input type="hidden" name="mana_current" value="{{ $character->mana_current }}">
        <input type="hidden" name="mana_max" value="{{ $character->mana_max }}">
    </form>
</div>

{{-- ── Ajouter un objet ────────────────────────────────────────────────── --}}
<section class="card section">
    <details>
        <summary style="cursor:pointer;padding:1rem"><strong>＋ Ajouter un objet</strong></summary>
        <form method="POST" action="{{ route('player.inventory.store') }}" class="card-body">
            @csrf
            <div class="form-grid">
                <div class="form-group"><label>Nom</label><input class="input" name="name" required></div>
                <div class="form-group"><label>Catégorie</label><input class="input" name="category" value="Divers" required></div>
                <div class="form-group"><label>Quantité</label><input class="input" type="number" min="1" name="quantity" value="1" required></div>
                <div class="form-group"><label>Poids (kg)</label><input class="input" type="number" step="0.01" min="0" name="weight" value="0" required></div>
                <div class="form-group full"><label>Description</label><input class="input" name="description"></div>
                <label class="check"><input type="checkbox" name="equipped" value="1"> Équipé</label>
            </div>
            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.8rem">Ajouter</button>
        </form>
    </details>
</section>

@forelse($groupedItems as $category => $categoryItems)
<section class="section card">
    <header class="card-header"><h2>{{ $category }}</h2><span class="badge">{{ $categoryItems->sum('quantity') }} unité(s)</span></header>
    <div class="card-body list">
        @foreach($categoryItems as $item)
            <div class="list-row" style="flex-direction:column;align-items:stretch;gap:.4rem">
                <div class="actions" style="justify-content:space-between">
                    <div>
                        <div class="actions">
                            <strong>{{ $item->name }}</strong>
                            @if($item->equipped)<span class="badge badge-gold">Équipé</span>@endif
                            <span class="badge">× {{ $item->quantity }}</span>
                        </div>
                        <div class="small muted">{{ $item->description ?: 'Aucune description.' }}</div>
                    </div>
                    <div class="actions">
                        <label class="btn btn-ghost btn-sm" for="item-{{ $item->id }}" title="Modifier">✎</label>
                        <form method="POST" action="{{ route('player.inventory.destroy', $item) }}"
                              onsubmit="return confirm('Retirer {{ $item->name }} de ton inventaire ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm" type="submit" title="Supprimer">🗑</button>
                        </form>
                    </div>
                </div>

                {{-- Édition dépliable, sans JavaScript --}}
                <input class="sheet-tabs" type="checkbox" id="item-{{ $item->id }}" hidden>
                <form method="POST" action="{{ route('player.inventory.update', $item) }}" class="item-edit">
                    @csrf @method('PUT')
                    <div class="gm-inline-form">
                        <div class="form-group"><label>Nom</label><input class="input input-xs" name="name" value="{{ $item->name }}" required></div>
                        <div class="form-group"><label>Catégorie</label><input class="input input-xs" name="category" value="{{ $item->category }}" required></div>
                        <div class="form-group"><label>Qté</label><input class="input input-xs" type="number" min="1" name="quantity" value="{{ $item->quantity }}" required></div>
                        <div class="form-group"><label>Poids</label><input class="input input-xs" type="number" step="0.01" min="0" name="weight" value="{{ $item->weight }}" required></div>
                        <div class="form-group" style="flex:1;min-width:12rem"><label>Description</label><input class="input input-xs" name="description" value="{{ $item->description }}"></div>
                        <label class="check"><input type="checkbox" name="equipped" value="1" @checked($item->equipped)> Équipé</label>
                        <button class="btn btn-secondary btn-sm" type="submit">Enregistrer</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</section>
@empty
<div class="card empty section">Ton sac est vide.</div>
@endforelse
@endsection
