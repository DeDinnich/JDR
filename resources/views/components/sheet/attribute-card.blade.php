@props(['attribute', 'editable' => false, 'characterId' => null])

<div class="card stat-card" data-attribute-id="{{ $attribute['id'] }}"
     @if($attribute['description']) title="{{ $attribute['description'] }}" @endif>
    <span class="stat-abbr">{{ $attribute['abbreviation'] }}</span>
    <span class="stat-name">{{ $attribute['name'] }}</span>
    <span class="stat-value">{{ $attribute['display'] }}</span>
    @if($attribute['modifier'] !== 0)
        <span class="stat-sub" data-attribute-modifier>{{ $attribute['modifier'] > 0 ? '+' : '' }}{{ $attribute['modifier'] }} de modificateur</span>
    @else
        <span class="stat-sub" data-attribute-modifier></span>
    @endif

    @if($editable)
        <button class="stat-card-action" type="button" aria-label="Gérer {{ $attribute['name'] }}"
                data-attribute-open
                data-url="{{ route('gm.attributes.update', [$characterId, $attribute['id']]) }}"
                data-name="{{ $attribute['name'] }}"
                data-value="{{ $attribute['value'] }}"
                data-modifier="{{ $attribute['modifier'] }}"></button>
    @endif
</div>
