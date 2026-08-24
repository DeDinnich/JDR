@props(['resources', 'characterId'])

<div class="resource-bars live-resources"
     data-character-resources="{{ $characterId }}"
     data-resource-url="{{ route('characters.resources.update', $characterId) }}">
    <label class="resource">
        <span class="resource-label">
            <span class="eyebrow">Vie</span>
            <span class="resource-value" data-resource-value="health">{{ $resources['health'] }} / {{ $resources['max_health'] }}</span>
        </span>
        <input class="resource-range resource-range-health" type="range" min="0" max="{{ $resources['max_health'] }}"
               value="{{ $resources['health'] }}" data-resource="health" aria-label="Points de vie"
               style="--resource-progress: {{ $resources['health_percentage'] }}%">
    </label>
    <label class="resource">
        <span class="resource-label">
            <span class="eyebrow">Mana</span>
            <span class="resource-value" data-resource-value="mana_current">{{ $resources['mana'] }} / {{ $resources['mana_max'] }}</span>
        </span>
        <input class="resource-range resource-range-mana" type="range" min="0" max="{{ max(1, $resources['mana_max']) }}"
               value="{{ $resources['mana'] }}" data-resource="mana_current" aria-label="Mana" @disabled($resources['mana_max'] === 0)
               style="--resource-progress: {{ $resources['mana_percentage'] }}%">
    </label>
    <span class="small muted live-save-status" data-resource-status aria-live="polite"></span>
</div>
