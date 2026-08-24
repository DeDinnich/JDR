@extends('layouts.app')

{{--
    Glossaire joueur.
    $npcs vient de NpcPresenter::glossaryFor() : il ne contient que les PNJ
    rencontrés, et pour chacun, uniquement les informations révélées à ce joueur.
--}}

@section('title', 'Glossaire')
@section('content')
<div class="page-heading">
    <div class="eyebrow">Ce que tu sais du monde</div>
    <h1>Glossaire</h1>
    <p>{{ count($npcs) }} personne(s) rencontrée(s).</p>
</div>

<div class="grid grid-3">
    @forelse($npcs as $npc)
        <article class="card glossary-card">
            <div class="glossary-portrait">
                @if($npc['portrait_path'])<img src="{{ $npc['portrait_path'] }}" alt="Portrait de {{ $npc['name'] }}">
                @else<span>{{ $npc['initials'] }}</span>@endif
            </div>
            <div class="card-body">
                <div class="actions">
                    <div>
                        <strong>{{ $npc['name'] }}</strong>
                        @if($npc['relationship'])<div class="eyebrow">{{ $npc['relationship'] }}</div>@endif
                    </div>
                </div>

                @if($npc['known_location'])<p class="metric-note">Vu à {{ $npc['known_location'] }}</p>@endif

                @if($npc['informations'])
                    <p class="metric-note">{{ count($npc['informations']) }} information(s) connue(s)</p>
                @endif

                <a class="btn btn-secondary btn-sm" href="{{ route('player.glossary.show', $npc['id']) }}">Ouvrir la fiche</a>
            </div>
        </article>
    @empty
        <div class="card empty span-2">Tu n’as encore rencontré personne dont tu te souviennes.</div>
    @endforelse
</div>
@endsection
