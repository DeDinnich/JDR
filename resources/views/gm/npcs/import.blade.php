@extends('layouts.app')

@section('title', 'Importer des PNJ')
@section('context', 'Table du maître du jeu')

@section('content')
<div class="page-heading">
    <div class="eyebrow"><a href="{{ route('gm.npcs.index') }}">← Retour aux PNJ</a></div>
    <h1>Importer des PNJ</h1>
    <p>Colle ici le JSON produit par ton assistant. « Analyser » ne touche pas à la base : tu vois d’abord ce qui a été compris.</p>
</div>

@if($result)
    <section class="card" style="margin-bottom:1rem">
        <header class="card-header">
            <h2>Résultat de l’analyse</h2>
            @if($result['ok'])
                <span class="badge badge-green">{{ count($result['npcs'] ?? []) }} PNJ détecté(s)</span>
            @else
                <span class="badge">{{ count($result['errors']) }} erreur(s)</span>
            @endif
        </header>
        <div class="card-body">
            @if($result['errors'])
                <div class="list">
                    @foreach($result['errors'] as $error)
                        <div class="list-row"><span class="muted">{{ $error }}</span></div>
                    @endforeach
                </div>
            @endif

            @if(($result['duplicates'] ?? []) !== [])
                <p class="metric-note">
                    {{ count($result['duplicates']) }} doublon(s) qui seront ignorés :
                    {{ implode(', ', $result['duplicates']) }}.
                </p>
            @endif

            @if($result['ok'] && ($result['npcs'] ?? []) !== [])
                <div class="list">
                    @foreach($result['npcs'] as $row)
                        <div class="list-row">
                            <strong>{{ trim($row['first_name'].' '.($row['last_name'] ?? '')) }}</strong>
                            <span class="muted small">{{ $row['profession'] ?? $row['role'] ?? '—' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif

<section class="card">
    <form method="POST" action="{{ route('gm.npcs.import.analyse') }}" class="card-body">
        @csrf
        <div class="form-group">
            <label for="json">JSON</label>
            <textarea class="input" id="json" name="json" rows="16" style="font-family:monospace">{{ $json }}</textarea>
        </div>
        <div class="actions">
            <button class="btn btn-secondary" type="submit">Analyser</button>
            <button class="btn btn-primary" type="submit" formaction="{{ route('gm.npcs.import.store') }}">Importer</button>
        </div>
    </form>
</section>

<section class="card section">
    <details>
        <summary style="cursor:pointer;padding:1rem"><strong>Voir le format JSON attendu</strong></summary>
        <div class="card-body">
            <p><strong>Champ obligatoire :</strong> <code>first_name</code>. Tout le reste est facultatif.</p>
            <p><strong>Champs optionnels :</strong> <code>last_name</code>, <code>nickname</code>, <code>title</code>,
                <code>age</code>, <code>gender</code>, <code>race</code>, <code>profession</code>, <code>role</code>,
                <code>house</code> (slug : valtheris, aerendis, vaelmont, veyre), <code>location</code> (nom exact du lieu),
                <code>public_description</code>, <code>personality</code>, <code>gm_notes</code>,
                <code>status</code> (vivant / mort / disparu / inconnu),
                <code>importance</code> (figurant / secondaire / majeur / central), <code>tags</code> (liste de textes).</p>
            <p><strong>secrets</strong> : liste d’objets <code>{ "title": "...", "content": "..." }</code> — <code>title</code> obligatoire. Strictement MJ.</p>
            <p><strong>revealable_information</strong> : liste d’objets <code>{ "title": "...", "content": "...", "category": "..." }</code>.
                Catégories : identite, relation, profession, rumeur, histoire, autre. Aucune n’est révélée à l’import.</p>
            <p class="metric-note">Un PNJ dont le prénom + nom existe déjà est ignoré et signalé, jamais écrasé.</p>
            <pre style="overflow-x:auto"><code>{{ $example }}</code></pre>
        </div>
    </details>
</section>
@endsection
