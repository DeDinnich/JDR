@extends('layouts.app')

@section('title', $sheet['identity']['name'])
@section('context', 'Compagnon de route')

@section('content')
@php($identity = $sheet['identity'])
@php($resources = $sheet['resources'])

<div class="page-heading">
    <div class="eyebrow"><a href="{{ route('player.character') }}#tab-allies">← Retour à ma fiche</a></div>
    <h1>{{ $identity['name'] }}</h1>
    <p>Joué par {{ $sheet['player_name'] }}@if($sheet['house']) · {{ $sheet['house'] }}@endif</p>
</div>

<section class="card sheet-hero">
    <div class="sheet-portrait">
        @if($identity['portrait_path'])<img src="{{ $identity['portrait_path'] }}" alt="Portrait de {{ $identity['name'] }}">
        @else{{ $identity['initials'] }}@endif
    </div>
    <div class="sheet-identity">
        <div class="eyebrow">{{ $identity['adventurer_title'] ?: 'Compagnon' }}</div>
        <h2>{{ $identity['name'] }}</h2>
        <p class="sheet-traits">
            <span>{{ $identity['age_label'] }}</span>
            @if($identity['race'])<span>{{ $identity['race'] }}</span>@endif
            @if($identity['occupation'])<span>{{ $identity['occupation'] }}</span>@endif
        </p>
        <div class="resource-bars">
            <div class="resource"><div class="resource-label"><span class="eyebrow">Vie</span><span>{{ $resources['health'] }} / {{ $resources['max_health'] }}</span></div><div class="gauge gauge-health"><span style="width:{{ $resources['health_percentage'] }}%"></span></div></div>
            <div class="resource"><div class="resource-label"><span class="eyebrow">Mana</span><span>{{ $resources['mana'] }} / {{ $resources['mana_max'] }}</span></div><div class="gauge gauge-mana"><span style="width:{{ $resources['mana_percentage'] }}%"></span></div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-title"><div><h2>Caractéristiques</h2><p>Toutes les valeurs connues de ce compagnon.</p></div></div>
    <div class="stat-grid">
        @foreach($sheet['attributes'] as $attribute)
            <div class="card stat-card"><span class="stat-abbr">{{ $attribute['abbreviation'] }}</span><span class="stat-name">{{ $attribute['name'] }}</span><span class="stat-value">{{ $attribute['display'] }}</span></div>
        @endforeach
    </div>
</section>

<section class="section">
    <div class="section-title"><div><h2>Compétences</h2><p>Compétences actuellement connues.</p></div></div>
    @foreach($sheet['skills'] as $category => $skills)
        <div class="skill-group"><div class="skill-group-title"><span class="eyebrow">{{ $category }}</span></div><div class="skill-list">
            @foreach($skills as $skill) @include('components.sheet.skill-row', ['skill' => $skill]) @endforeach
        </div></div>
    @endforeach
</section>

@if($sheet['masteries']->isNotEmpty())
<section class="section">
    <div class="section-title"><div><h2>Maîtrises</h2></div></div>
    <div class="grid grid-2">
        @foreach($sheet['masteries']->flatten(1) as $mastery) @include('components.sheet.mastery-card', ['mastery' => $mastery]) @endforeach
    </div>
</section>
@endif
@endsection
