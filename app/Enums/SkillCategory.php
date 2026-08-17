<?php

namespace App\Enums;

enum SkillCategory: string
{
    case Physical = 'physique';
    case Social = 'sociale';
    case Knowledge = 'connaissance';
    case Magic = 'magie';
    case Craft = 'artisanat';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physiques',
            self::Social => 'Sociales',
            self::Knowledge => 'Connaissances',
            self::Magic => 'Magie',
            self::Craft => 'Artisanat',
        };
    }

    public function isMagical(): bool
    {
        return $this === self::Magic;
    }
}
