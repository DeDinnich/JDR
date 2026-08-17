<?php

namespace App\Enums;

enum AbilityType: string
{
    case Spell = 'sort';
    case Technique = 'technique';
    case Special = 'capacite';
    case Talent = 'talent';
    case Other = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Spell => 'Sort',
            self::Technique => 'Technique martiale',
            self::Special => 'Capacité spéciale',
            self::Talent => 'Talent',
            self::Other => 'Autre',
        };
    }

    public function isMagical(): bool
    {
        return $this === self::Spell;
    }
}
