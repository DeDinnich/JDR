<?php

namespace App\Http\Requests\Gm;

use App\Services\Bestiary\DiceRoller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MonsterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:180',
                Rule::unique('monsters', 'name')->ignore($this->route('monster')),
            ],
            'type' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'string', 'max:64'],
            'threat_level' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:5000'],
            'habitat' => ['nullable', 'string', 'max:255'],
            'behavior' => ['nullable', 'string', 'max:5000'],
            'game_master_notes' => ['nullable', 'string', 'max:5000'],
            'portrait' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'health_current' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'health_max' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'mana_current' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'mana_max' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'armor' => ['nullable', 'integer', 'min:0', 'max:999'],
            'strength' => ['nullable', 'integer', 'min:0', 'max:999'],
            'endurance' => ['nullable', 'integer', 'min:0', 'max:999'],
            'dexterity' => ['nullable', 'integer', 'min:0', 'max:999'],
            'intelligence' => ['nullable', 'integer', 'min:0', 'max:999'],
            'willpower' => ['nullable', 'integer', 'min:0', 'max:999'],
            'perception' => ['nullable', 'integer', 'min:0', 'max:999'],
            'damage_dice' => ['nullable', 'string', 'regex:'.DiceRoller::FORMULA_PATTERN],
            'weakness' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->integer('health_current') > $this->integer('health_max')) {
                $validator->errors()->add('health_current', 'La vie actuelle ne peut pas dépasser la vie maximale.');
            }

            if ($this->integer('mana_current') > $this->integer('mana_max')) {
                $validator->errors()->add('mana_current', 'Le mana actuel ne peut pas dépasser le mana maximal.');
            }
        }];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->safe()->except('portrait');
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du monstre est obligatoire.',
            'name.unique' => 'Un monstre porte déjà ce nom.',
            'portrait.max' => 'Le portrait ne doit pas dépasser 4 Mo.',
            'portrait.mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
            'damage_dice.regex' => 'La formule de dégâts doit suivre le format 2d6+3.',
        ];
    }
}
