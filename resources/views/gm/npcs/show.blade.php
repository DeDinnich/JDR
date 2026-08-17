@extends('layouts.app')

{{--
    Fiche PNJ — vue MJ.
    Tout est modifiable sur place. Les secrets et les informations révélables
    se disent à voix haute à table : ils restent en base (import/export JSON)
    mais n'encombrent pas cet écran, qui doit rester lisible en pleine partie.
--}}

@section('title', $npc->fullName())
@section('context', 'Table du maître du jeu')

@section('content')
<div class="page-heading">
    <div class="eyebrow"><a href="{{ route('gm.npcs.index') }}">← Retour aux PNJ</a></div>
    <h1>{{ $npc->fullName() }}</h1>
    <p>{{ $npc->role ?: $npc->profession ?: 'Rôle non défini' }} · {{ $npc->importance->label() }} · {{ $npc->status->label() }}</p>
</div>

<div class="actions" style="margin-bottom:1rem">
    <a class="btn btn-secondary btn-sm" href="{{ route('gm.npcs.export.one', $npc) }}">Exporter ce PNJ</a>
    <form method="POST" action="{{ route('gm.npcs.destroy', $npc) }}" onsubmit="return confirm('Supprimer définitivement ce PNJ ?')">
        @csrf @method('DELETE')
        <button class="btn btn-ghost btn-sm" type="submit">Supprimer</button>
    </form>
</div>

{{-- ── Révélation ─────────────────────────────────────────────────────── --}}
<section class="card">
    <header class="card-header">
        <div><h2>Révéler ce personnage</h2><p class="muted small" style="margin:.2rem 0 0">Les joueurs choisis le découvrent immédiatement dans leur glossaire.</p></div>
        <span class="badge badge-gold">{{ $npc->discoveredBy->count() }} joueur(s) le connaissent</span>
    </header>
    <form method="POST" action="{{ route('gm.npcs.detail.reveal', $npc) }}" class="card-body">
        @csrf
        <div class="form-group">
            <label>Joueurs</label>
            @foreach($players as $player)
                <label class="check">
                    <input type="checkbox" name="user_ids[]" value="{{ $player->id }}"
                           @checked($npc->discoveredBy->contains($player->id))>
                    {{ $player->name }}
                    @if($npc->discoveredBy->contains($player->id))<span class="muted small">· connaît déjà</span>@endif
                </label>
            @endforeach
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Révéler</button>
    </form>
</section>

{{-- ── Identité ───────────────────────────────────────────────────────── --}}
<section class="card section">
    <header class="card-header"><h2>Identité</h2></header>
    <form method="POST" action="{{ route('gm.npcs.detail.update', $npc) }}" class="card-body">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group"><label>Prénom</label><input class="input" name="first_name" value="{{ old('first_name', $npc->first_name ?: $npc->name) }}" required></div>
            <div class="form-group"><label>Nom</label><input class="input" name="last_name" value="{{ old('last_name', $npc->last_name) }}"></div>
            <div class="form-group"><label>Surnom</label><input class="input" name="nickname" value="{{ old('nickname', $npc->nickname) }}"></div>
            <div class="form-group"><label>Titre</label><input class="input" name="title" value="{{ old('title', $npc->title) }}"></div>
            <div class="form-group"><label>Âge</label><input class="input" type="number" min="0" name="age" value="{{ old('age', $npc->age) }}"></div>
            <div class="form-group"><label>Genre</label><input class="input" name="gender" value="{{ old('gender', $npc->gender) }}"></div>
            <div class="form-group"><label>Race</label><input class="input" name="race" value="{{ old('race', $npc->race) }}"></div>
            <div class="form-group"><label>Profession</label><input class="input" name="profession" value="{{ old('profession', $npc->profession) }}"></div>
            <div class="form-group"><label>Rôle</label><input class="input" name="role" value="{{ old('role', $npc->role) }}"></div>
            <div class="form-group"><label>Portrait (URL)</label><input class="input" name="portrait_path" value="{{ old('portrait_path', $npc->portrait_path) }}"></div>

            <div class="form-group">
                <label>Maison</label>
                <select class="select" name="house_id">
                    <option value="">Aucune</option>
                    @foreach($houses as $house)<option value="{{ $house->id }}" @selected($npc->house_id === $house->id)>{{ $house->name }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Importance</label>
                <select class="select" name="importance">
                    @foreach($importances as $value => $label)<option value="{{ $value }}" @selected($npc->importance->value === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select class="select" name="status">
                    @foreach($statuses as $value => $label)<option value="{{ $value }}" @selected($npc->status->value === $value)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label>Tags (séparés par des virgules)</label><input class="input" name="tags" value="{{ old('tags', implode(', ', $npc->tags ?? [])) }}"></div>
        </div>

        <div class="form-group"><label>Description publique</label><textarea class="input" name="description" rows="3">{{ old('description', $npc->description) }}</textarea></div>
        <div class="form-group"><label>Personnalité</label><textarea class="input" name="personality" rows="3">{{ old('personality', $npc->personality) }}</textarea></div>
        <div class="form-group"><label>Notes MJ <span class="badge">Jamais envoyées au joueur</span></label><textarea class="input" name="game_master_notes" rows="3">{{ old('game_master_notes', $npc->game_master_notes) }}</textarea></div>

        <button class="btn btn-primary btn-sm" type="submit">Enregistrer</button>
    </form>
</section>

@endsection
