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
            <div class="map-card-image" style="background-image:url('{{ $map->image_path ?: '/images/maps/royaumes-oublies.svg' }}')"></div>
            <div class="card-body">
                <div class="actions" style="justify-content:space-between"><span class="eyebrow">Zone découverte</span>@if($map->is_active)<span class="badge badge-green">Zone actuelle</span>@endif</div>
                <h2 class="display" style="margin:.55rem 0;font-size:1.45rem">{{ $map->title }}</h2>
                <p class="muted small" style="margin:0 0 1rem">{{ Str::limit($map->description, 120) }}</p>
                <span class="badge badge-gold">{{ $map->locations_count }} lieu(x) connu(s)</span>
            </div>
        </a>
    @empty
        <div class="card empty span-2">La carte est encore couverte de brume.</div>
    @endforelse
</div>
@endsection
