{{--
    Ligne de compétence, partagée par la fiche joueur et la fiche MJ.
    $skill provient toujours du CharacterSheetPresenter. La clé 'reveal_state'
    n'existe que côté MJ : une compétence cachée n'est tout simplement pas
    présente dans le payload joueur.
--}}
@php($state = $skill['reveal_state'] ?? 'revealed')
<div class="skill-row {{ $state === 'hidden' ? 'is-unknown' : '' }}">
    <div>
        <strong>{{ $skill['name'] }}</strong>
        <span class="skill-attrs">{{ $skill['attributes'] }}</span>
        @if($state === 'hidden')<span class="badge">Cachée du joueur</span>@endif
    </div>
    <span class="skill-score">
        {{ $skill['display'] }}
        @if($skill['bonus'] !== 0)
            <span class="small muted">({{ $skill['base_value'] }} {{ $skill['bonus'] > 0 ? '+' : '−' }} {{ abs($skill['bonus']) }})</span>
        @endif
    </span>
</div>
