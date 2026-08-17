{{--
    Affinité pour une école de magie. Le niveau exact n'est présent dans les
    données que si le MJ l'a révélé ; sinon 'level' vaut null.
--}}
@php($levels = config('jdr.character.affinity_levels'))
@php($color = $affinity['color'] ?: '#caa55b')

<div class="affinity-row" style="color: {{ $color }}">
    <span class="affinity-dot" style="background: {{ $color }}"></span>
    <strong class="small" style="color: var(--text); min-width: 7rem">{{ $affinity['school'] }}</strong>

    <span class="affinity-meter">
        @foreach(array_slice($levels, 1) as $index => $level)
            <i class="{{ $affinity['level'] !== null && $index < $affinity['level'] ? 'is-filled' : '' }}"></i>
        @endforeach
    </span>

    <span class="small" style="color: var(--muted); min-width: 6rem; text-align: right">
        {{ $affinity['level_label'] }}
    </span>
</div>
