<?php

namespace App\Http\Requests\Gm;

use App\Enums\RevealState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CharacterAbilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'ability_definition_id' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'integer', 'exists:ability_definitions,id',
            ],
            'unlocked' => ['nullable', 'boolean'],
            'reveal_state' => ['required', Rule::enum(RevealState::class)],
            'gm_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function payload(): array
    {
        return [
            ...collect($this->validated())->except(['ability_definition_id', 'unlocked'])->all(),
            'unlocked' => $this->boolean('unlocked'),
        ];
    }
}
