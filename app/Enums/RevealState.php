<?php

namespace App\Enums;

/**
 * État de révélation d'une donnée de fiche personnage.
 *
 * Le personnage possède toujours une valeur réelle en base ; cet état décrit
 * uniquement ce que le JOUEUR est autorisé à connaître. Le filtrage est
 * appliqué côté serveur par le CharacterSheetPresenter : une donnée Hidden
 * n'est jamais sérialisée vers la vue joueur.
 */
enum RevealState: string
{
    /** Le joueur ignore tout de cette donnée. */
    case Hidden = 'hidden';

    /** Le joueur en a une impression qualitative, sans valeur chiffrée. */
    case Approximate = 'approximate';

    /** Le joueur connaît la valeur exacte. */
    case Revealed = 'revealed';

    public function label(): string
    {
        return match ($this) {
            self::Hidden => 'Inconnue',
            self::Approximate => 'Approximative',
            self::Revealed => 'Révélée',
        };
    }

    /** La valeur chiffrée réelle peut-elle être transmise au joueur ? */
    public function exposesExactValue(): bool
    {
        return $this === self::Revealed;
    }

    /** Le joueur sait-il au moins que cette donnée existe ? */
    public function isDiscovered(): bool
    {
        return $this !== self::Hidden;
    }

    public function nextStep(): self
    {
        return match ($this) {
            self::Hidden => self::Approximate,
            self::Approximate, self::Revealed => self::Revealed,
        };
    }
}
