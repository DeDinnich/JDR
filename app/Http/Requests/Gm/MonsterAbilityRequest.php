<?php

namespace App\Http\Requests\Gm;

use App\Services\Bestiary\DiceRoller;
use Illuminate\Foundation\Http\FormRequest;

class MonsterAbilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:5000'],
            'dice_formula' => ['nullable', 'string', 'regex:'.DiceRoller::FORMULA_PATTERN],
            'mana_cost' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'cooldown' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la capacité est obligatoire.',
            'dice_formula.regex' => 'La formule de dés doit suivre le format 2d6+3.',
        ];
    }
}
