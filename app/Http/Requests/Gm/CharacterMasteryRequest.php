<?php

namespace App\Http\Requests\Gm;

use App\Enums\RevealState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CharacterMasteryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        $maximumRank = count(config('jdr.character.mastery_ranks')) - 1;

        return [
            // Requis uniquement à la création : on rattache une définition du catalogue.
            'mastery_definition_id' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'integer', 'exists:mastery_definitions,id',
            ],
            'rank_index' => ['required', 'integer', 'min:0', 'max:'.$maximumRank],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'reveal_state' => ['required', Rule::enum(RevealState::class)],
            'gm_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function payload(): array
    {
        return collect($this->validated())->except('mastery_definition_id')->all();
    }
}
