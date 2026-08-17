<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ressources que le joueur tient à jour lui-même pendant la partie : PV, mana
 * et bourse. Le site est un compagnon de table, pas un arbitre — c'est le MJ
 * qui décide à voix haute, le joueur qui note.
 */
class UpdateResourcesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->character()->exists() === true;
    }

    public function rules(): array
    {
        return [
            'health' => ['required', 'integer', 'min:0', 'max:9999'],
            'max_health' => ['required', 'integer', 'min:1', 'max:9999'],
            'mana_current' => ['required', 'integer', 'min:0', 'max:9999'],
            'mana_max' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'gold' => ['required', 'integer', 'min:0', 'max:99999999'],
        ];
    }
}
