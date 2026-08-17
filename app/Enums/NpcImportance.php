<?php

namespace App\Enums;

/**
 * Poids narratif d'un PNJ, utilisé pour trier et filtrer la liste MJ.
 *
 * L'ordre des cases va du moins au plus important : `weight()` permet de
 * remonter les figures centrales en tête de liste pendant une partie.
 */
enum NpcImportance: string
{
    case Background = 'figurant';
    case Secondary = 'secondaire';
    case Major = 'majeur';
    case Central = 'central';

    public function label(): string
    {
        return match ($this) {
            self::Background => 'Figurant',
            self::Secondary => 'Secondaire',
            self::Major => 'Majeur',
            self::Central => 'Central',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Background => 0,
            self::Secondary => 1,
            self::Major => 2,
            self::Central => 3,
        };
    }
}
