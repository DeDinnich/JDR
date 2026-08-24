<x-mail::message>
# Un nouveau sceau pour votre grimoire

Bonjour **{{ $characterName }}**,

Une demande a été faite pour renouveler le mot de passe de votre compte sur **Le Fil d’Ambre**.

<x-mail::button :url="$url">
Choisir un nouveau mot de passe
</x-mail::button>

Ce passage restera ouvert pendant **{{ $expiresIn }} minutes**. Après ce délai, il faudra demander un nouveau lien.

Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer ce message : votre mot de passe actuel reste inchangé.

À bientôt autour de la table,  
**Le maître des chroniques**
</x-mail::message>
