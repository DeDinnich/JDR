<?php

namespace App\Services\Bestiary;

use Illuminate\Validation\ValidationException;

class DiceRoller
{
    public const FORMULA_PATTERN = '/^(?<count>\d{1,2})d(?<sides>\d{1,4})(?<modifier>[+-]\d{1,4})?$/i';

    /** @return array{formula: string, rolls: array<int, int>, modifier: int, total: int} */
    public function roll(string $formula): array
    {
        $formula = strtolower(trim($formula));

        if (! preg_match(self::FORMULA_PATTERN, $formula, $parts)) {
            throw ValidationException::withMessages(['dice_formula' => 'Formule de dés invalide. Exemple : 2d6+3.']);
        }

        $count = (int) $parts['count'];
        $sides = (int) $parts['sides'];
        $modifier = isset($parts['modifier']) ? (int) $parts['modifier'] : 0;

        if ($count < 1 || $count > 50 || $sides < 2 || $sides > 1000) {
            throw ValidationException::withMessages([
                'dice_formula' => 'Une formule accepte de 1 à 50 dés ayant entre 2 et 1000 faces.',
            ]);
        }

        $rolls = [];

        for ($index = 0; $index < $count; $index++) {
            $rolls[] = random_int(1, $sides);
        }

        return [
            'formula' => $formula,
            'rolls' => $rolls,
            'modifier' => $modifier,
            'total' => max(0, array_sum($rolls) + $modifier),
        ];
    }
}
