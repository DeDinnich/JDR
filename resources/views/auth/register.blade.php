@extends('layouts.guest')

@section('content')
<div class="login-page">
    <section class="login-art">
        <div class="login-copy">
            <div class="eyebrow">Chroniques de Valdoren</div>
            <h1>Rejoignez<br>la compagnie</h1>
            <p>Choisissez le nom qui sera inscrit dans la chronique. Le maître du jeu complétera ensuite votre fiche et vos aptitudes.</p>
        </div>
    </section>
    <section class="login-panel">
        <div class="login-box">
            <div class="eyebrow">Nouvel aventurier</div>
            <h2 class="display" style="font-size:2.35rem;margin:.5rem 0 .35rem">Rejoindre la campagne</h2>
            <p class="muted" style="margin:0 0 1.5rem">Trois informations suffisent pour prendre place à la table.</p>

            @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

            <form method="POST" action="{{ route('register.store') }}" class="stack">
                @csrf
                <div class="form-group">
                    <label for="character_name">Nom du personnage</label>
                    <input class="input" id="character_name" name="character_name" value="{{ old('character_name') }}" autocomplete="nickname" maxlength="100" required autofocus>
                </div>
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    <span class="muted small">8 caractères minimum, avec au moins une lettre et un chiffre.</span>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirmer le mot de passe</label>
                    <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="btn btn-primary" type="submit">Rejoindre la campagne</button>
            </form>

            <hr class="divider">
            <p class="muted small" style="text-align:center;margin:.8rem 0">Vous avez déjà un personnage ?</p>
            <a class="btn btn-secondary" style="width:100%" href="{{ route('login') }}">Retour à la connexion</a>
        </div>
    </section>
</div>
@endsection
