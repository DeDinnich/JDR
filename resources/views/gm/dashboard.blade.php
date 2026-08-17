@extends('layouts.app')

@section('title', 'Table du maître du jeu')
@section('content')
<div class="page-heading">
    <div class="eyebrow">Vue de la tablée</div>
    <h1>La chronique est entre vos mains</h1>
    <p>Surveillez l’état du groupe, intervenez sur les fiches et glissez une information à l’oreille d’un seul aventurier.</p>
</div>

<div class="grid grid-4">
    @forelse($players as $player)
        @php($character = $player->character)
        <a class="card card-link" href="{{ route('gm.characters.show', $character) }}">
            <div class="card-body">
                <div class="npc-row"><span class="avatar">{{ collect(explode(' ', $character->name))->map(fn($part) => mb_substr($part,0,1))->take(2)->implode('') }}</span><div><span class="eyebrow">{{ $player->name }}</span><br><strong class="display" style="font-size:1.15rem">{{ $character->name }}</strong></div></div>
                <div style="margin-top:1rem"><div class="small muted" style="display:flex;justify-content:space-between;margin-bottom:.35rem"><span>{{ $character->status }}</span><span>{{ $character->health }} / {{ $character->max_health }} PV</span></div><div class="health-bar"><span style="width:{{ $character->healthPercentage() }}%"></span></div></div>
                <hr class="divider">
                <div class="actions" style="justify-content:space-between"><span class="badge">{{ $character->currentMap?->title ?? 'Sans zone' }}</span><span class="small gold">Gérer →</span></div>
            </div>
        </a>
    @empty
        <div class="card empty span-2">Aucun joueur n’a encore rejoint la campagne.</div>
    @endforelse
</div>

<div class="grid grid-2 section">
    <section class="card">
        <header class="card-header"><div><h2>Message secret</h2><p class="muted small" style="margin:.2rem 0 0">Diffusion immédiate et privée.</p></div><span class="badge badge-gold">Temps réel</span></header>
        @if($players->isNotEmpty())
        <form method="POST" action="{{ route('gm.messages.store') }}" class="card-body stack">
            @csrf
            <div class="form-grid">
                <div class="form-group"><label for="recipient_id">Destinataire</label><select class="select" id="recipient_id" name="recipient_id" required>@foreach($players as $player)<option value="{{ $player->id }}" @selected(old('recipient_id') == $player->id)>{{ $player->character->name }} · {{ $player->name }}</option>@endforeach</select></div>
                <div class="form-group full"><label for="body">Ce que lui seul doit savoir</label><textarea class="textarea" id="body" name="body" rows="6" maxlength="2000" placeholder="Tu remarques que l’homme au fond porte le même symbole…" required>{{ old('body') }}</textarea></div>
            </div>
            <button class="btn btn-primary" type="submit">Transmettre le secret</button>
        </form>
        @else
            <div class="empty">Le premier message pourra être envoyé dès qu’un joueur sera inscrit.</div>
        @endif
    </section>

    <section class="card">
        <header class="card-header"><h2>Derniers messages</h2><span class="badge">Accusés de lecture</span></header>
        <div class="card-body list">
            @forelse($messages as $message)
                <div class="list-row"><div><div class="actions"><strong>{{ $message->recipient->name }}</strong></div><div class="small muted">{{ Str::limit($message->body, 82) }}</div><div class="eyebrow" style="margin-top:.3rem">{{ $message->created_at->diffForHumans() }}</div></div><span data-message-id="{{ $message->id }}" class="badge {{ $message->read_at ? 'badge-green' : 'badge-gold' }}">{{ $message->read_at ? 'Lu' : 'En attente' }}</span></div>
            @empty<div class="empty">Aucun message envoyé.</div>@endforelse
        </div>
    </section>
</div>

<section class="section">
    <div class="section-title"><div><h2>Commandes rapides</h2><p>Les leviers utiles pendant la partie.</p></div></div>
    <div class="grid grid-3">
        <a class="card card-link card-body" href="{{ route('gm.world.index') }}"><span class="eyebrow">Exploration</span><h3 class="display" style="font-size:1.3rem;margin:.45rem 0">Révéler une zone</h3><p class="muted small">Carte, lieu ou personnage, pour un joueur ou toute la table.</p></a>
        @if($players->isNotEmpty())
            <a class="card card-link card-body" href="{{ route('gm.characters.show', $players->first()->character) }}"><span class="eyebrow">Intervention</span><h3 class="display" style="font-size:1.3rem;margin:.45rem 0">Modifier une fiche</h3><p class="muted small">PV, états, caractéristiques, compétences et inventaire.</p></a>
        @else
            <div class="card card-body"><span class="eyebrow">Intervention</span><h3 class="display" style="font-size:1.3rem;margin:.45rem 0">En attente des joueurs</h3><p class="muted small">Les fiches apparaîtront ici après leur inscription.</p></div>
        @endif
        <div class="card card-body"><span class="eyebrow">État du groupe</span><h3 class="display" style="font-size:1.3rem;margin:.45rem 0">{{ $players->sum(fn($player) => $player->character->health) }} PV cumulés</h3><p class="muted small">{{ $players->where(fn($player) => $player->character->healthPercentage() < 35)->count() }} aventurier(s) en situation critique.</p></div>
    </div>
</section>
@endsection
