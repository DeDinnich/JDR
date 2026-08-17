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

<section class="card section">
    <header class="card-header">
        <div><h2>Tes notes</h2><p class="muted small" style="margin:.2rem 0 0">Personnelles. Elles n’apparaissent que pour toi.</p></div>
    </header>
    <form method="POST" action="{{ route('player.glossary.notes', $npc['id']) }}" class="card-body">
        @csrf @method('PUT')
        <div class="form-group">
            <label for="relationship">Ce que tu ressens</label>
            @php($current = $npc['relationship'] ?: 'neutre')
            <select class="select" id="relationship" name="relationship">
                @foreach(['allie' => 'Allié', 'neutre' => 'Neutre', 'mefiance' => 'Méfiance', 'ennemi' => 'Ennemi'] as $value => $label)
                    <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="personal_notes">Ce que tu en penses</label>
            <textarea class="input" id="personal_notes" name="personal_notes" rows="5"
                      placeholder="Je pense qu’il ment.">{{ old('personal_notes', $npc['personal_notes']) }}</textarea>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Enregistrer</button>
    </form>
</section>
@endsection
