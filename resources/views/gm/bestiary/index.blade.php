@extends('layouts.app')

@section('title', 'Bestiaire')
@section('context', 'Table du maître du jeu')

@section('content')
<div class="page-heading">
    <div class="eyebrow">Créatures et adversaires</div>
    <h1>Bestiaire du maître</h1>
    <p>{{ $monsters->count() }} créature(s) préparée(s). Les joueurs ne voient que celles que tu leur révèles.</p>
</div>

<div class="actions" style="margin-bottom:1rem">
    <label class="btn btn-primary" for="monster-create">＋ Créer un monstre</label>
    <a class="btn btn-secondary" href="{{ route('gm.bestiary.import.show') }}">Importer en JSON</a>
    <a class="btn btn-secondary" href="{{ route('gm.bestiary.export') }}">Exporter le bestiaire</a>
</div>

<section class="card">
    <form method="GET" action="{{ route('gm.bestiary.index') }}" class="card-body gm-inline-form">
        <div class="form-group" style="flex:1;min-width:14rem">
            <label for="recherche">Recherche</label>
            <input class="input" id="recherche" name="recherche" value="{{ $search }}" placeholder="Nom, type ou habitat">
        </div>
        <button class="btn btn-secondary" type="submit">Rechercher</button>
    </form>
</section>

<section class="section">
    <div class="grid grid-4">
        @forelse($monsters as $monster)
            <a class="card card-link monster-card" href="{{ route('gm.bestiary.show', $monster) }}">
                <div class="monster-card-portrait">
                    @if($monster->portrait_path)
                        <img src="{{ $monster->portrait_path }}" alt="Portrait de {{ $monster->name }}">
                    @else
                        <span>{{ $monster->initials() }}</span>
                    @endif
                    <span class="badge badge-gold monster-known-count">
                        Connu de {{ $monster->discovered_by_count }}/{{ $playersCount }}
                    </span>
                </div>
                <div class="monster-card-copy">
                    <h2>{{ $monster->name }}</h2>
                    <p>{{ $monster->type ?: 'Type inconnu' }}@if($monster->threat_level) · {{ $monster->threat_level }}@endif</p>
                </div>
            </a>
        @empty
            <div class="card empty span-2">Aucun monstre dans le bestiaire.</div>
        @endforelse
    </div>
</section>

<input class="modal-toggle" type="checkbox" id="monster-create" hidden @if($errors->any()) checked @endif>
<div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="monster-create-title">
    <label class="modal-backdrop" for="monster-create" aria-hidden="true"></label>
    <div class="modal-panel card">
        <header class="card-header">
            <h2 id="monster-create-title">Créer un monstre</h2>
            <label class="btn btn-ghost btn-sm" for="monster-create" title="Fermer">✕</label>
        </header>
        <form method="POST" action="{{ route('gm.bestiary.store') }}" enctype="multipart/form-data" class="card-body">
            @csrf
            <div class="form-grid">
                <div class="form-group"><label>Nom</label><input class="input" name="name" value="{{ old('name') }}" required></div>
                <div class="form-group"><label>Type</label><input class="input" name="type" value="{{ old('type') }}" placeholder="Bête, mort-vivant, aberration…"></div>
                <div class="form-group"><label>Niveau de menace</label><input class="input" name="threat_level" value="{{ old('threat_level') }}" placeholder="Mineur, dangereux, boss…"></div>
                <div class="form-group"><label>Portrait</label><input class="input" type="file" name="portrait" accept="image/jpeg,image/png,image/webp"></div>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:1rem">Créer et paramétrer</button>
        </form>
    </div>
</div>
@endsection
