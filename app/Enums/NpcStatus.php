<?php

namespace App\Enums;

/** État civil narratif d'un PNJ. */
enum NpcStatus: string
{
    case Alive = 'vivant';
    case Dead = 'mort';
    case Missing = 'disparu';
    case Unknown = 'inconnu';

    public function label(): string
    {
        return match ($this) {
            self::Alive => 'Vivant',
            self::Dead => 'Mort',
            self::Missing => 'Disparu',
            self::Unknown => 'Inconnu',
        };
    }
}
