@extends('layouts.app')

{{--
    Journal personnel — même écran pour le joueur et le maître du jeu.

    Une note = un éditeur de texte riche qui s'enregistre seul en arrière-plan
    dès que l'on cesse d'écrire. Les notes appartiennent au compte : personne
    ne voit celles d'un autre, MJ compris.
--}}

@section('title', 'Journal')
@section('content')
<div class="page-heading">
    <div class="eyebrow">Mémoire de l’aventure</div>
    <h1>Journal</h1>
    <p>Tes notes ne sont visibles que par toi. Elles s’enregistrent toutes seules.</p>
</div>

<div class="actions" style="margin-bottom:1rem">
    <form method="POST" action="{{ route($routePrefix.'.store') }}">
        @csrf
        <input type="hidden" name="title" value="Nouvelle note">
        <button class="btn btn-primary" type="submit">＋ Nouvelle note</button>
    </form>
</div>

<div class="stack">
    @forelse($notes as $note)
        <section class="card note-editor" data-note-url="{{ route($routePrefix.'.update', $note) }}">
            <header class="card-header">
                <input class="input note-title" name="title" value="{{ $note->title }}" maxlength="180"
                       aria-label="Titre de la note">
                <div class="actions">
                    <span class="badge note-status" aria-live="polite">Enregistrée à {{ $note->updated_at->format('H:i') }}</span>
                    <form method="POST" action="{{ route($routePrefix.'.destroy', $note) }}"
                          onsubmit="return confirm('Supprimer cette note ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm" type="submit" title="Supprimer">🗑</button>
                    </form>
                </div>
            </header>

            {{-- Barre d'outils : chaque bouton applique une commande de mise en forme --}}
            <div class="note-toolbar" role="toolbar" aria-label="Mise en forme">
                <button type="button" class="btn btn-ghost btn-sm" data-command="bold" title="Gras"><strong>G</strong></button>
                <button type="button" class="btn btn-ghost btn-sm" data-command="italic" title="Italique"><em>I</em></button>
                <button type="button" class="btn btn-ghost btn-sm" data-command="underline" title="Souligné"><u>S</u></button>
                <button type="button" class="btn btn-ghost btn-sm" data-command="strikeThrough" title="Barré"><s>B</s></button>
                <span class="toolbar-separator" aria-hidden="true"></span>
                <button type="button" class="btn btn-ghost btn-sm" data-command="formatBlock" data-value="h2" title="Titre">T</button>
                <button type="button" class="btn btn-ghost btn-sm" data-command="formatBlock" data-value="blockquote" title="Citation">❝</button>
                <button type="button" class="btn btn-ghost btn-sm" data-command="insertUnorderedList" title="Liste à puces">•</button>
                <button type="button" class="btn btn-ghost btn-sm" data-command="insertOrderedList" title="Liste numérotée">1.</button>
                <span class="toolbar-separator" aria-hidden="true"></span>
                <button type="button" class="btn btn-ghost btn-sm" data-command="removeFormat" title="Effacer la mise en forme">✕</button>
            </div>

            <div class="note-content" contenteditable="true" role="textbox" aria-multiline="true"
                 aria-label="Contenu de la note">{!! $note->content !!}</div>

            <div class="card-body" style="padding-top:0">
                <label class="check">
                    <input type="checkbox" class="note-pinned" @checked($note->pinned)> Épingler cette note
                </label>
            </div>
        </section>
    @empty
        <div class="card empty">Ton journal attend sa première trace.</div>
    @endforelse
</div>
@endsection
