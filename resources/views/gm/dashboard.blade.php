@extends('layouts.app')

@section('title', 'Table du maître du jeu')
@section('content')
<div class="page-heading">
    <div class="eyebrow">Vue de la tablée</div>
    <h1>La chronique est entre vos mains</h1>
    <p>Surveillez l’état du groupe, intervenez sur les fiches et glissez une information à l’oreille d’un seul aventurier.</p>
</div>

<div class="grid grid-4">
    @forelse($playerCards as $card)
        @php($player = $card['player'])
        @php($character = $card['character'])
        <article class="card gm-player-card">
            <div class="card-body">
                <a class="npc-row gm-player-card-link" href="{{ route('gm.characters.show', $character) }}">
                    <span class="avatar">
                        @if($character->portrait_path)<img src="{{ $character->portrait_path }}" alt="">@else{{ collect(explode(' ', $character->displayName()))->map(fn($part) => mb_substr($part,0,1))->take(2)->implode('') }}@endif
                    </span>
                    <span><span class="eyebrow">{{ $player->name }}</span><br><strong class="display" style="font-size:1.15rem">{{ $character->displayName() }}</strong></span>
                </a>
                <x-sheet.resource-sliders :resources="$card['resources']" :character-id="$character->id" />
                <hr class="divider">
                <div class="actions" style="justify-content:space-between"><span class="small muted">{{ $character->status }}</span><a class="small gold" href="{{ route('gm.characters.show', $character) }}">Gérer →</a></div>
            </div>
        </article>
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
                <div class="list-row" data-secret-message-row="{{ $message->id }}">
                    <div>
                        <div class="actions"><strong>{{ $message->recipient->name }}</strong></div>
                        <div class="small muted">{{ Str::limit($message->body, 82) }}</div>
                        <div class="eyebrow" style="margin-top:.3rem">{{ $message->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="message-row-actions">
                        <span data-message-id="{{ $message->id }}" class="badge {{ $message->read_at ? 'badge-green' : 'badge-gold' }}">{{ $message->read_at ? 'Lu' : 'En attente' }}</span>
                        <form method="POST" action="{{ route('messages.destroy', $message) }}" data-secret-message-delete-form>
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-icon danger" type="submit" title="Supprimer des deux côtés" aria-label="Supprimer ce message des deux côtés">🗑</button>
                        </form>
                    </div>
                </div>
            @empty<div class="empty">Aucun message envoyé.</div>@endforelse
        </div>
    </section>
</div>

<section class="section">
    <div class="section-title">
        <div><h2>Extraction de séance</h2><p>Créez un instantané JSON de la fiche et des connaissances des joueurs sélectionnés.</p></div>
        <span class="badge badge-gold">Prêt pour l’IA</span>
    </div>
    <form class="card" method="POST" action="{{ route('gm.session-extractions.store') }}">
        @csrf
        <div class="card-body stack">
            @if($players->isNotEmpty())
                <div class="session-player-selector">
                    @foreach($players as $player)
                        <label class="session-player-option">
                            <input type="checkbox" name="user_ids[]" value="{{ $player->id }}" @checked(in_array($player->id, old('user_ids', $players->pluck('id')->all())))>
                            <span class="avatar">
                                @if($player->character->portrait_path)<img src="{{ $player->character->portrait_path }}" alt="">@else{{ collect(explode(' ', $player->character->displayName()))->map(fn($part) => mb_substr($part,0,1))->take(2)->implode('') }}@endif
                            </span>
                            <span><strong>{{ $player->character->displayName() }}</strong><br><span class="small muted">{{ $player->name }}</span></span>
                        </label>
                    @endforeach
                </div>
                <div class="actions extraction-actions">
                    <p class="small muted">Inclut fiche, caractéristiques, compétences connues, inventaire visible, notes, glossaire personnel, monde découvert et messages secrets conservés.</p>
                    <button class="btn btn-primary" type="submit">Télécharger l’extraction JSON</button>
                </div>
            @else
                <div class="empty">L’extraction sera disponible dès qu’un joueur aura rejoint la campagne.</div>
            @endif
        </div>
    </form>
</section>
@endsection
