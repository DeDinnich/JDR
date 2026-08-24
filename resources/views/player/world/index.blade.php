@extends('layouts.app')

@section('title', 'Cartes & zones')
@section('content')
<div class="page-heading">
    <div class="eyebrow">Le monde connu</div>
    <h1>Cartes & zones</h1>
    <p>Seules les terres révélées au cours de votre voyage apparaissent ici. Les brumes cachent encore le reste.</p>
</div>

<div class="grid grid-3">
    @forelse($maps as $map)
        <a class="card card-link" href="{{ route('player.world.show', $map) }}">
            <img class="map-card-image" src="{{ $map->hasGrid() ? route('maps.preview', $map) : '/images/maps/royaumes-oublies.svg' }}" alt="Terres révélées sur {{ $map->title }}" loading="lazy">
            <div class="card-body">
                <div class="actions" style="justify-content:space-between"><span class="eyebrow">Zone découverte</span>@if($map->is_active)<span class="badge badge-green">Zone actuelle</span>@endif</div>
                <h2 class="display" style="margin:.55rem 0;font-size:1.45rem">{{ $map->title }}</h2>
                @if($map->description)<p class="muted small" style="margin:0">{{ Str::limit($map->description, 120) }}</p>@endif
            </div>
        </a>
    @empty
        <div class="card empty span-2">La carte est encore couverte de brume.</div>
    @endforelse
</div>
@endsection
