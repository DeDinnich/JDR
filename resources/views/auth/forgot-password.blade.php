@extends('layouts.guest')

@section('title', 'Sceau oublié')

@section('content')
<div class="login-page">
    <section class="login-art">
        <div class="login-copy">
            <div class="eyebrow">Chroniques de Valdoren</div>
            <h1>Retrouvez<br>votre grimoire</h1>
            <p>Un messager peut vous remettre un nouveau sceau pour rouvrir les pages de votre personnage.</p>
        </div>
    </section>
    <section class="login-panel">
        <div class="login-box">
            <div class="eyebrow">Sceau oublié</div>
            <h2 class="display" style="font-size:2.35rem;margin:.5rem 0 .35rem">Recevoir un nouveau lien</h2>
            <p class="muted" style="margin:0 0 1.5rem">Indiquez l’adresse utilisée pour rejoindre la campagne.</p>

            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

            <form method="POST" action="{{ route('password.email') }}" class="stack">
                @csrf
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
                <button class="btn btn-primary" type="submit">Envoyer le lien</button>
            </form>

            <hr class="divider">
            <a class="btn btn-secondary" style="width:100%" href="{{ route('login') }}">Retour à la connexion</a>
        </div>
    </section>
</div>
@endsection
