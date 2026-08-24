@extends('layouts.app')

@section('title', 'Chat')
@section('context', 'Messages privés')
@section('body-class', 'chat-page')

@section('content')
<div class="chat-page-content">
<div class="page-heading">
    <div class="eyebrow">Entre quatre yeux</div>
    <h1>Chat privé</h1>
    <p>Chaque échange reste strictement entre ses deux participants.</p>
</div>

<div class="chat-layout" data-chat @if($selected) data-conversation-id="{{ $selected->id }}" data-read-url="{{ route('chat.read', $selected) }}" @endif>
    <aside class="card chat-contacts" aria-label="Conversations">
        @forelse($conversations as $conversation)
            @php($contact = $conversation->otherParticipant(auth()->user()))
            <a href="{{ route('chat.show', $conversation) }}"
               class="chat-contact {{ $selected?->is($conversation) ? 'is-active' : '' }}"
               data-chat-contact="{{ $conversation->id }}">
                <span class="chat-avatar">
                    @if($contact->character?->portrait_path)
                        <img src="{{ $contact->character->portrait_path }}" alt="">
                    @else
                        {{ collect(explode(' ', $contact->name))->map(fn($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}
                    @endif
                </span>
                <span class="chat-contact-copy">
                    <strong>{{ $contact->name }}</strong>
                    <span>{{ $contact->isGameMaster() ? 'Maître du jeu' : 'Joueur' }}</span>
                </span>
                <span class="badge badge-gold {{ $conversation->unread_count ? '' : 'is-hidden' }}"
                      data-chat-unread="{{ $conversation->id }}">{{ $conversation->unread_count }}</span>
            </a>
        @empty
            <div class="empty">Aucun autre membre n’a encore rejoint la campagne.</div>
        @endforelse
    </aside>

    <section class="card chat-panel">
        @if($selected && $other)
            <header class="card-header chat-header">
                <div>
                    <div class="eyebrow">Conversation privée</div>
                    <h2>{{ $other->name }}</h2>
                </div>
                <span class="typing-indicator" data-typing-indicator aria-live="polite"></span>
            </header>

            <div class="chat-messages" data-chat-messages>
                @foreach($messages as $message)
                    <article class="chat-message {{ $message->sender_id === auth()->id() ? 'is-mine' : '' }}"
                             data-message-id="{{ $message->id }}">
                        <div class="chat-bubble">{{ $message->body }}</div>
                        <span>{{ $message->sender_id === auth()->id() ? 'Vous' : $message->sender->name }} · {{ $message->created_at->format('H:i') }}</span>
                    </article>
                @endforeach
            </div>

            <form class="chat-composer" data-chat-form action="{{ route('chat.messages.store', $selected) }}">
                <label class="sr-only" for="chat-body">Message</label>
                <div class="chat-compose-field">
                    <textarea class="textarea" id="chat-body" name="body" rows="2" maxlength="4000"
                              aria-describedby="chat-shortcut"
                              placeholder="Écrire un message privé…" required data-chat-input></textarea>
                    <span id="chat-shortcut">Entrée pour envoyer · Maj+Entrée pour aller à la ligne</span>
                </div>
                <button class="btn btn-primary" type="submit">Envoyer</button>
            </form>
        @else
            <div class="empty">Sélectionne une conversation.</div>
        @endif
    </section>
</div>
</div>
@endsection
