@extends('layouts.app')

@section('title', $npc->name)
@section('content')
<div class="page-heading">
    <div class="eyebrow">Rencontre · <a href="{{ route('player.notes.index') }}">Retour au glossaire</a></div>
    <h1>{{ $npc->name }}</h1>
    <p>{{ $npc->role ?: 'Rôle inconnu' }} · rencontré à {{ $npc->location?->name ?? 'un endroit oublié' }}</p>
</div>

<div class="grid grid-2">
    <section class="card">
        <div class="card-body" style="padding:1.6rem">
            <div class="npc-row"><span class="npc-avatar" style="width:5rem;height:5rem;font-size:1.4rem">{{ $npc->initials() }}</span><div><span class="badge badge-gold">{{ $npc->role ?: 'Inconnu' }}</span><h2 class="display" style="font-size:1.8rem;margin:.5rem 0">{{ $npc->name }}</h2><span class="muted small">{{ $npc->location?->map?->title }} · {{ $npc->location?->name }}</span></div></div>
            <hr class="divider">
            <p class="muted" style="line-height:1.75">{{ $npc->description ?: 'Vous ne savez encore presque rien de cette personne.' }}</p>
        </div>
    </section>

    <section class="card">
        <header class="card-header"><h2>Mon impression</h2><span class="badge">Privé</span></header>
        <form method="POST" action="{{ route('player.npcs.update', $npc) }}" class="card-body stack">
            @csrf @method('PUT')
            <div class="form-group"><label for="relationship">Relation perçue</label><select class="select" id="relationship" name="relationship"><option value="allie" @selected($npc->pivot->relationship === 'allie')>Allié</option><option value="neutre" @selected($npc->pivot->relationship === 'neutre')>Neutre</option><option value="mefiance" @selected($npc->pivot->relationship === 'mefiance')>Méfiance</option><option value="ennemi" @selected($npc->pivot->relationship === 'ennemi')>Ennemi</option></select></div>
            <div class="form-group"><label for="personal_notes">Notes personnelles</label><textarea class="textarea" id="personal_notes" name="personal_notes" rows="11" placeholder="Ce qu’il vous inspire, vos soupçons, ses promesses…">{{ $npc->pivot->personal_notes }}</textarea></div>
            <button class="btn btn-primary" type="submit">Conserver mon impression</button>
        </form>
    </section>
</div>
@endsection
