@extends('layouts.app')

{{--
    Monde — vue MJ.

    Une carte = une image importée puis découpée en quadrillage. Le placement
    de lieux a été retiré : ce sont désormais les repères posés à la main sur
    la carte qui jouent ce rôle, côté MJ comme côté joueurs.
--}}

@section('title', 'Monde')
@section('content')
<div class="page-heading">
    <div class="eyebrow">Construction du monde</div>
    <h1>Monde</h1>
    <p>Importe une carte, découpe-la, puis ouvre les cases au fil de l’exploration.</p>
</div>

<section class="card">
    <header class="card-header">
        <div><h2>＋ Importer une carte</h2><p class="muted small" style="margin:.2rem 0 0">L’image est découpée en tuiles. Toutes les cases démarrent dans le noir.</p></div>
    </header>
    <form method="POST" action="{{ route('gm.maps.store') }}" enctype="multipart/form-data" class="card-body">
        @csrf
        <div class="form-grid">
            <div class="form-group"><label>Titre</label><input class="input" name="title" required></div>
            <div class="form-group"><label>Image (JPEG, PNG ou WebP — 12 Mo max)</label><input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div>
            <div class="form-group"><label>Colonnes</label><input class="input" type="number" min="1" max="40" name="grid_columns" value="10" required></div>
            <div class="form-group"><label>Lignes</label><input class="input" type="number" min="1" max="40" name="grid_rows" value="8" required></div>
            <div class="form-group full"><label>Description</label><textarea class="textarea" name="description" rows="2"></textarea></div>
        </div>
        <button class="btn btn-primary" type="submit" style="margin-top:.8rem">Importer et découper</button>
    </form>
</section>

<section class="section">
    <div class="section-title"><div><h2>Cartes</h2><p>{{ $maps->count() }} carte(s) préparée(s).</p></div></div>
    <div class="grid grid-3">
        @forelse($maps as $map)
            <a class="card card-link map-preview-card" href="{{ route('gm.maps.grid', $map) }}">
                <img class="map-card-image" src="{{ $map->hasGrid() ? route('maps.preview', $map) : '/images/maps/royaumes-oublies.svg' }}" alt="Aperçu complet de {{ $map->title }}" loading="lazy">
                <div class="card-body">
                    <div class="actions" style="justify-content:space-between">
                        <div><span class="eyebrow">{{ $map->discoveredBy->count() }} joueur(s) l’ont</span><h3 class="display">{{ $map->title }}</h3></div>
                        @if($map->is_active)<span class="badge badge-green">Active</span>@endif
                    </div>
                </div>
            </a>
        @empty
            <div class="card empty span-2">Aucune carte préparée. Importe une image pour commencer.</div>
        @endforelse
    </div>
</section>
@endsection
