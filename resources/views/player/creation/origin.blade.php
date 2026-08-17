@extends('layouts.app')

@section('title', 'Ton origine')
@section('content')

<div class="page-heading">
    <div class="eyebrow">Révélation</div>
    <h1>{{ $house['name'] }}</h1>
    @if($house['motto'])<p><em>« {{ $house['motto'] }} »</em></p>@endif
</div>

<section class="section card" @if($house['color']) style="border-top: 3px solid {{ $house['color'] }}" @endif>
    <header class="card-header"><h2>Ta famille</h2><span class="badge">{{ $house['specialty'] }}</span></header>
    <div class="card-body">
        <p>{{ $house['description'] }}</p>
        @if($house['reputation'])<p class="metric-note">{{ $house['reputation'] }}</p>@endif
    </div>
</section>

<section class="section card">
    <header class="card-header"><h2>Les premiers visages</h2><span class="badge">{{ count($parents) }}</span></header>
    <div class="card-body list">
        @forelse($parents as $parent)
        <div class="list-row">
            <span class="brand-mark">{{ $parent['initials'] }}</span>
            <div>
                <strong>{{ $parent['name'] }}</strong>
                <span class="badge">{{ $parent['relation'] }}</span>
                @if($parent['title'])<div class="eyebrow">{{ $parent['title'] }}</div>@endif
                @if($parent['description'])<p class="metric-note">{{ $parent['description'] }}</p>@endif
            </div>
        </div>
        @empty
        <p class="metric-note">Tu n’as encore reconnu personne.</p>
        @endforelse
    </div>
</section>

<section class="section card">
    <div class="card-body">
        <p>Tu ne sais rien de toi encore : ni ta force, ni ton esprit, ni ce que le mana fera de toi. Tout cela viendra.</p>
        <a class="button" href="{{ route('player.character') }}">Voir ma fiche</a>
    </div>
</section>

@endsection
