<?php

namespace App\Enums;

enum MasteryCategory: string
{
    case Magic = 'magie';
    case Weapon = 'arme';
    case Combat = 'combat';
    case Craft = 'artisanat';
    case Other = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Magic => 'Magie',
            self::Weapon => 'Arme',
            self::Combat => 'Combat',
            self::Craft => 'Artisanat',
            self::Other => 'Autre',
        };
    }
}
