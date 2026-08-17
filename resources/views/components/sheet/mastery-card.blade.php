{{-- Carte de maîtrise : rang atteint sur l'échelle Novice → Divin. --}}
@php($ranks = config('jdr.character.mastery_ranks'))

<article class="card mastery-card">
    <div class="mastery-head">
        <strong>{{ $mastery['name'] }}</strong>
        <span class="rank-pill">{{ $mastery['rank_label'] }}</span>
    </div>

    @if($mastery['description'])
        <p class="muted small" style="margin:0">{{ $mastery['description'] }}</p>
    @endif

    {{-- Échelle des rangs : chaque cran franchi est marqué. --}}
    <div class="rank-track" role="img" aria-label="Rang {{ $mastery['rank_label'] }} sur {{ count($ranks) }}">
        @foreach($ranks as $index => $rank)
            <span class="rank-step {{ $index <= $mastery['rank_index'] ? 'is-filled' : '' }}" title="{{ $rank }}"></span>
        @endforeach
    </div>

    @if($mastery['progress'] > 0)
        <div class="resource">
            <div class="resource-label">
                <span class="eyebrow">Vers {{ $ranks[$mastery['rank_index'] + 1] ?? 'l’ultime' }}</span>
                <span class="resource-value">{{ $mastery['progress'] }} %</span>
            </div>
            <div class="gauge gauge-xp"><span style="width:{{ $mastery['progress'] }}%"></span></div>
        </div>
    @endif

    @if($mastery['school'])
        <span class="badge badge-gold" style="justify-self:start">{{ $mastery['school'] }}</span>
    @endif
</article>
