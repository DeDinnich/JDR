{{--
    Ligne de compétence, partagée par la fiche joueur et la fiche MJ.
    $skill provient toujours du CharacterSheetPresenter. La clé 'reveal_state'
    n'existe que côté MJ : une compétence cachée n'est tout simplement pas
    présente dans le payload joueur.
--}}
@php($state = $skill['reveal_state'] ?? 'revealed')
@php($editableBonus = $editableBonus ?? false)
<div class="skill-row {{ $state === 'hidden' ? 'is-unknown' : '' }}" data-skill-id="{{ $skill['id'] }}">
    <div>
        <strong>{{ $skill['name'] }}</strong>
        <span class="skill-attrs">{{ $skill['attributes'] }}</span>
        @if($state === 'hidden')<span class="badge">Cachée du joueur</span>@endif
    </div>
    <span class="skill-score" data-skill-score>
        <span data-skill-display>{{ $skill['display'] }}</span>
        <span class="small muted" data-skill-breakdown>@if($skill['bonus'] !== 0)({{ $skill['base_value'] }} {{ $skill['bonus'] > 0 ? '+' : '−' }} {{ abs($skill['bonus']) }})@endif</span>
    </span>
    @if($editableBonus)
        @php($isGameMasterEditor = $editableBonus === 'gm')
        <button class="skill-row-action" type="button" aria-label="Gérer le bonus de {{ $skill['name'] }}"
                data-skill-open
                data-mode="{{ $isGameMasterEditor ? 'gm' : 'player' }}"
                data-url="{{ $isGameMasterEditor ? route('gm.skills.update', [$characterId, $skill['id']]) : route('player.skills.bonus.update', $skill['id']) }}"
                data-name="{{ $skill['name'] }}" data-base="{{ $skill['base_value'] }}"
                data-gm-bonus="{{ $skill['gm_bonus'] }}" data-player-bonus="{{ $skill['player_bonus'] }}"
                @if($isGameMasterEditor)
                    data-gm-notes="{{ $skill['gm_notes'] }}" data-reveal-state="{{ $skill['reveal_state'] }}"
                @endif></button>
    @endif
</div>
