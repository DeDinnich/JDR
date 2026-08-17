{{-- Sort ou technique. Repliée par défaut : la fiche reste lisible en partie. --}}
<details class="ability-card">
    <summary>
        <div>
            <strong>{{ $ability['name'] }}</strong>
            <div class="small muted">
                {{ $ability['type_label'] }}@if($ability['mastery']) · {{ $ability['mastery'] }}@endif
            </div>
        </div>
        <div class="actions">
            @if($ability['mana_cost'])<span class="badge mana-chip">{{ $ability['mana_cost'] }} mana</span>@endif
            @if($ability['minimum_rank'])<span class="badge">{{ $ability['minimum_rank'] }}</span>@endif
        </div>
    </summary>

    <div class="ability-body">
        <p class="muted small" style="margin:0;line-height:1.65">{{ $ability['description'] ?: 'Aucune description consignée.' }}</p>

        @if(!empty($ability['details']))
            <div class="ability-meta">
                @foreach($ability['details'] as $key => $value)
                    <span class="badge">{{ ucfirst($key) }} : {{ is_array($value) ? implode(', ', $value) : $value }}</span>
                @endforeach
            </div>
        @endif
    </div>
</details>
