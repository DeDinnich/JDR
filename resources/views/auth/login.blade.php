@extends('layouts.guest')

@section('content')
<div class="login-page">
    <section class="login-art">
        <div class="login-copy">
            <div class="eyebrow">Chroniques de Valdoren</div>
            <h1>Le Fil<br>d’Ambre</h1>
            <p>Votre chronique vivante. Gardez vos secrets, suivez les pistes et laissez la carte se dévoiler au fil de l’aventure.</p>
        </div>
    </section>
    <section class="login-panel">
        <div class="login-box">
            <div class="eyebrow">Rejoindre la table</div>
            <h2 class="display" style="font-size:2.35rem;margin:.5rem 0 .35rem">Entrez dans la chronique</h2>
            <p class="muted" style="margin:0 0 1.5rem">Utilisez vos identifiants pour retrouver votre personnage.</p>

            @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

            <form method="POST" action="{{ route('login.store') }}" class="stack">
                @csrf
                <div class="form-group">
                    <label for="email">Adresse de l’aventurier</label>
                    <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Sceau secret</label>
                    <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <label class="check"><input type="checkbox" name="remember" value="1"> Garder ma place à la table</label>
                <button class="btn btn-primary" type="submit">Ouvrir mon grimoire</button>
            </form>

            <hr class="divider">
            <p class="muted small" style="text-align:center;margin:.8rem 0">Vous rejoignez la campagne pour la première fois ?</p>
            <a class="btn btn-secondary" style="width:100%" href="{{ route('register') }}">Créer mon aventurier</a>
        </div>
    </section>
</div>
@endsection
