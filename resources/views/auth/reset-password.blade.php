@extends('layouts.guest')

@section('title', 'Nouveau sceau')

@section('content')
<div class="login-page">
    <section class="login-art">
        <div class="login-copy">
            <div class="eyebrow">Chroniques de Valdoren</div>
            <h1>Forgez un<br>nouveau sceau</h1>
            <p>Choisissez un mot de passe inédit pour protéger les secrets et les souvenirs de votre personnage.</p>
        </div>
    </section>
    <section class="login-panel">
        <div class="login-box">
            <div class="eyebrow">Nouveau sceau</div>
            <h2 class="display" style="font-size:2.35rem;margin:.5rem 0 .35rem">Renouveler le mot de passe</h2>
            <p class="muted" style="margin:0 0 1.5rem">Le lien n’est utilisable qu’une fois et expire après une heure.</p>

            @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

            <form method="POST" action="{{ route('password.update') }}" class="stack">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input class="input" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Nouveau mot de passe</label>
                    <input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    <span class="muted small">8 caractères minimum, avec au moins une lettre et un chiffre.</span>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirmer le mot de passe</label>
                    <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="btn btn-primary" type="submit">Sceller le nouveau mot de passe</button>
            </form>
        </div>
    </section>
</div>
@endsection
