{{--
    Sélecteur de visibilité d'un élément de fiche, côté MJ.

    Trois boutons — Caché / Approximatif / Révélé — qui postent chacun sur la
    route de révélation. Un clic suffit en pleine partie, et le serveur reste
    seul juge de ce qui part ensuite vers le joueur.

    Paramètres : $character, $type (attribute|skill|mastery|affinity|ability),
    $id, $current (valeur de reveal_state).
--}}
@php($states = ['hidden' => 'Caché', 'approximate' => '≈', 'revealed' => 'Révélé'])

<div class="reveal-switch" role="group" aria-label="Visibilité pour le joueur">
    @foreach($states as $value => $label)
        <form method="POST" action="{{ route('gm.reveal.store', $character) }}">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="id" value="{{ $id }}">
            <input type="hidden" name="state" value="{{ $value }}">
            <button type="submit"
                    class="{{ $current === $value ? 'is-current' : '' }}"
                    title="{{ $value === 'approximate' ? 'Perception approximative' : $label }}">{{ $label }}</button>
        </form>
    @endforeach
</div>
