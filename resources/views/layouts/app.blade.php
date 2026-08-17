<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Le 6ᵉ Monde') · Compagnon JDR</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-user-id="{{ auth()->id() }}" data-user-role="{{ auth()->user()->role->value }}">
<div class="shell">
    <aside class="sidebar">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark">FA</span>
            <span><span class="brand-name">Le 6ᵉ Monde</span><br><span class="eyebrow">Chroniques de Valdoren</span></span>
        </a>

        <nav class="nav" aria-label="Navigation principale">
            @if(auth()->user()->isGameMaster())
                <a class="nav-link {{ request()->routeIs('gm.dashboard') ? 'active' : '' }}" href="{{ route('gm.dashboard') }}"><span class="nav-icon">✦</span>Vue de la tablée</a>
                {{-- `gm.npcs.*` est volontairement exclu ici : ce préfixe couvre
                     aussi la base de PNJ, qui a son propre lien juste en dessous. --}}
                <a class="nav-link {{ request()->routeIs('gm.world.*', 'gm.maps.*', 'gm.locations.*') ? 'active' : '' }}" href="{{ route('gm.world.index') }}"><span class="nav-icon">⌖</span>Monde</a>
                <a class="nav-link {{ request()->routeIs('gm.npcs.*') ? 'active' : '' }}" href="{{ route('gm.npcs.index') }}"><span class="nav-icon">☗</span>PNJ</a>
            @else
                <a class="nav-link {{ request()->routeIs('player.character') ? 'active' : '' }}" href="{{ route('player.character') }}"><span class="nav-icon">♙</span>Personnage</a>
                <a class="nav-link {{ request()->routeIs('player.inventory') ? 'active' : '' }}" href="{{ route('player.inventory') }}"><span class="nav-icon">◇</span>Inventaire</a>
                <a class="nav-link {{ request()->routeIs('player.world.*') ? 'active' : '' }}" href="{{ route('player.world.index') }}"><span class="nav-icon">⌖</span>Cartes & zones</a>
                <a class="nav-link {{ request()->routeIs('player.notes.*', 'player.npcs.*') ? 'active' : '' }}" href="{{ route('player.notes.index') }}"><span class="nav-icon">≡</span>Journal & rencontres</a>
                <a class="nav-link {{ request()->routeIs('player.glossary.*') ? 'active' : '' }}" href="{{ route('player.glossary.index') }}"><span class="nav-icon">☗</span>Glossaire</a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <span class="avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}</span>
                <span><strong class="small">{{ auth()->user()->name }}</strong><br><span class="eyebrow">{{ auth()->user()->isGameMaster() ? 'Maître du jeu' : (auth()->user()->character?->archetype ?? 'Aventurier') }}</span></span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost" type="submit">Quitter la partie</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="mobile-header"><button class="mobile-nav-toggle" type="button" data-nav-toggle aria-label="Ouvrir le menu">☰</button><span class="display">Le 6ᵉ Monde</span></div>
            <div class="desktop-context eyebrow">@yield('context', auth()->user()->isGameMaster() ? 'Table du maître du jeu' : 'Compagnon d’aventure')</div>
            <div class="badge badge-gold">● Session en cours</div>
        </header>

        <div class="page">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if($errors->any())
                <div class="alert alert-error"><strong>Impossible d’enregistrer.</strong> {{ $errors->first() }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

@if(!auth()->user()->isGameMaster())
<div class="secret-overlay" data-secret-overlay role="dialog" aria-modal="true" aria-live="assertive">
    <div class="secret-scroll" data-priority="important">
        <div class="eyebrow" data-secret-priority>Message du maître du jeu</div>
        <h2>Pour vos yeux seulement</h2>
        <p class="secret-body" data-secret-body></p>
        <button class="btn btn-primary" type="button" data-secret-dismiss>J’ai compris</button>
    </div>
</div>

{{-- Révélation d'une donnée de fiche, poussée en temps réel par le MJ. --}}
<div class="secret-overlay reveal-overlay" data-reveal-overlay role="dialog" aria-modal="true" aria-live="polite">
    <div class="secret-scroll">
        <div class="reveal-kind" data-reveal-kind>Caractéristique</div>
        <h2 data-reveal-headline></h2>
        <p class="secret-body" data-reveal-description></p>
        <div class="actions" style="justify-content:center">
            <a class="btn btn-primary" data-reveal-action href="{{ route('player.character') }}">Ouvrir ma fiche</a>
            <button class="btn btn-secondary" type="button" data-reveal-dismiss>Plus tard</button>
        </div>
    </div>
</div>
@endif
</body>
</html>
