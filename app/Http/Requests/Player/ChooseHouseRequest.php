<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Choix de la grande maison.
 *
 * La règle n'accepte qu'une maison assignable : le slug réservé (Veyre) est
 * rejeté ici même si quelqu'un le tape à la main dans la requête.
 */
class ChooseHouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->character()->exists() === true;
    }

    public function rules(): array
    {
        return [
            'house' => [
                'required',
                'string',
                // 1/0 plutôt que true/false : selon le pilote, un booléen PHP
                // ne se compare pas à l'entier stocké en base.
                Rule::exists('houses', 'slug')->where('is_active', 1)->where('is_reserved', 0),
            ],
        ];
    }

    public function messages(): array
    {
        return ['house.exists' => 'Cette origine n’est pas disponible.'];
    }
}
