@extends('layouts.app')

@section('title', $npc['name'])
@section('content')
<div class="page-heading">
    <div class="eyebrow"><a href="{{ route('player.glossary.index') }}">← Retour au glossaire</a></div>
    <h1>{{ $npc['name'] }}</h1>
    @if($npc['relationship'])<p>{{ $npc['relationship'] }}</p>@endif
</div>

<section class="card">
    <div class="card-body">
        <div class="actions">
            <span class="brand-mark">{{ $npc['initials'] }}</span>
            <div>
                @if($npc['nickname'])<div class="eyebrow">« {{ $npc['nickname'] }} »</div>@endif
                @if($npc['known_location'])<div class="small muted">Vu à {{ $npc['known_location'] }}</div>@endif
            </div>
        </div>
    </div>
</section>

<section class="card section">
    <header class="card-header">
        <div><h2>Ce que tu sais</h2></div>
        <span class="badge">{{ count($npc['informations']) }}</span>
    </header>
    <div class="card-body list">
        @forelse($npc['informations'] as $information)
            <div class="list-row">
                <div>
                    <strong>{{ $information['title'] }}</strong>
                    <span class="badge">{{ $information['category_label'] }}</span>
                    @if($information['content'])<div class="small muted">{{ $information['content'] }}</div>@endif
                </div>
            </div>
        @empty
            <div class="empty">Tu ne sais encore rien de précis sur cette personne.</div>
        @endforelse
    </div>
</section>

{{-- Même éditeur que le journal : barre d'outils et enregistrement
     automatique dès que le joueur cesse d'écrire. --}}
<section class="card section note-editor" data-note-url="{{ route('player.glossary.notes', $npc['id']) }}">
    <header class="card-header">
        <div>
            <h2>Tes notes</h2>
            <p class="muted small" style="margin:.2rem 0 0">Personnelles. Elles n’apparaissent que pour toi et ne touchent pas sa fiche.</p>
        </div>
        <div class="actions">
            <span class="badge note-status" aria-live="polite">Enregistrées</span>
        </div>
    </header>

    <div class="note-toolbar" role="toolbar" aria-label="Mise en forme">
        <button type="button" class="btn btn-ghost btn-sm" data-command="bold" title="Gras"><strong>G</strong></button>
        <button type="button" class="btn btn-ghost btn-sm" data-command="italic" title="Italique"><em>I</em></button>
        <button type="button" class="btn btn-ghost btn-sm" data-command="underline" title="Souligné"><u>S</u></button>
        <button type="button" class="btn btn-ghost btn-sm" data-command="strikeThrough" title="Barré"><s>B</s></button>
        <span class="toolbar-separator" aria-hidden="true"></span>
        <button type="button" class="btn btn-ghost btn-sm" data-command="formatBlock" data-value="h3" title="Titre">T</button>
        <button type="button" class="btn btn-ghost btn-sm" data-command="insertUnorderedList" title="Liste à puces">•</button>
        <button type="button" class="btn btn-ghost btn-sm" data-command="insertOrderedList" title="Liste numérotée">1.</button>
        <span class="toolbar-separator" aria-hidden="true"></span>
        <button type="button" class="btn btn-ghost btn-sm" data-command="removeFormat" title="Effacer la mise en forme">✕</button>
    </div>

    <div class="note-content" contenteditable="true" role="textbox" aria-multiline="true"
         aria-label="Tes notes sur {{ $npc['name'] }}">{!! $npc['personal_notes'] !!}</div>

    <div class="card-body" style="padding-top:0">
        <label class="check">
            Ce que tu ressens
            @php($current = $npc['relationship'] ?: 'neutre')
            <select class="select input-xs note-relationship" name="relationship" style="margin-left:.5rem">
                @foreach(['allie' => 'Allié', 'neutre' => 'Neutre', 'mefiance' => 'Méfiance', 'ennemi' => 'Ennemi'] as $value => $label)
                    <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </div>
</section>
@endsection
