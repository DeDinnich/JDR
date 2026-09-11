@extends('layouts.app')

@section('title', 'Bestiaire')
@section('context', 'Carnet d’aventurier')

@section('content')
<div class="page-heading">
    <div class="eyebrow">Créatures rencontrées</div>
    <h1>Notre bestiaire</h1>
    <p>{{ count($monsters) }} créature(s) observée(s). Chaque fiche contient uniquement tes propres déductions.</p>
</div>

<div class="grid grid-4">
    @forelse($monsters as $monster)
        <a class="card card-link monster-card" href="{{ route('player.bestiary.show', $monster['id']) }}">
            <div class="monster-card-portrait">
                @if($monster['portrait_path'])
                    <img src="{{ $monster['portrait_path'] }}" alt="Portrait de {{ $monster['name'] }}">
                @else
                    <span>{{ $monster['initials'] }}</span>
                @endif
            </div>
            <div class="monster-card-copy">
                <h2>{{ $monster['name'] }}</h2>
                <p>{{ $monster['type'] ?: 'Nature inconnue' }}@if($monster['size']) · {{ $monster['size'] }}@endif</p>
                @php($clues = collect($monster['knowledge'])->filter(fn($value) => filled($value))->count())
                <span class="badge badge-gold">{{ $clues }} piste(s) consignée(s)</span>
            </div>
        </a>
    @empty
        <div class="card empty span-2">Aucune créature n’a encore rejoint ton bestiaire.</div>
    @endforelse
</div>
@endsection
