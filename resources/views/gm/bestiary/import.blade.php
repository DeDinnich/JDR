@extends('layouts.app')

@section('title', 'Importer le bestiaire')
@section('context', 'Bestiaire du maître')

@section('content')
<div class="page-heading">
    <div class="eyebrow"><a href="{{ route('gm.bestiary.index') }}">← Retour au bestiaire</a></div>
    <h1>Import JSON de monstres</h1>
    <p>Copie le modèle pour ton IA, fais-le compléter, puis analyse le résultat avant toute écriture en base.</p>
</div>

<section class="card ai-payload-card">
    <header class="card-header">
        <div><div class="eyebrow">Payload pour l’IA</div><h2>Modèle complet</h2></div>
        <button class="btn btn-secondary" type="button" data-copy-target="monster-json-example">Copier le JSON</button>
    </header>
    <div class="card-body">
        <p class="muted">Demande à l’IA de conserver exactement cette structure et de renvoyer uniquement du JSON valide. Duplique l’objet dans <code>monsters</code> pour un import de masse.</p>
        <textarea class="textarea json-payload" id="monster-json-example" rows="20" readonly>{{ $example }}</textarea>
        <span class="small muted" data-copy-status aria-live="polite"></span>
    </div>
</section>

@if($result)
    <section class="card section">
        <header class="card-header">
            <h2>Résultat de l’analyse</h2>
            <span class="badge {{ $result['ok'] ? 'badge-green' : 'badge-red' }}">
                {{ $result['ok'] ? count($result['monsters'] ?? []).' monstre(s) valide(s)' : count($result['errors']).' erreur(s)' }}
            </span>
        </header>
        <div class="card-body">
            @foreach($result['errors'] as $error)<p class="form-error">{{ $error }}</p>@endforeach
            @if(($result['duplicates'] ?? []) !== [])<p class="muted">Doublons ignorés : {{ implode(', ', $result['duplicates']) }}</p>@endif
            @foreach($result['monsters'] ?? [] as $row)
                <div class="list-row"><strong>{{ $row['name'] }}</strong><span class="muted small">{{ count($row['abilities'] ?? []) }} capacité(s)</span></div>
            @endforeach
        </div>
    </section>
@endif

<section class="card section">
    <header class="card-header"><h2>JSON à intégrer</h2></header>
    <form method="POST" action="{{ route('gm.bestiary.import.analyse') }}" class="card-body">
        @csrf
        <div class="form-group">
            <label for="json">Payload JSON</label>
            <textarea class="textarea json-payload" id="json" name="json" rows="22" required>{{ $json }}</textarea>
        </div>
        <div class="actions" style="margin-top:1rem">
            <button class="btn btn-secondary" type="submit">Analyser sans importer</button>
            <button class="btn btn-primary" type="submit" formaction="{{ route('gm.bestiary.import.store') }}">Importer le lot validé</button>
        </div>
    </form>
</section>
@endsection
