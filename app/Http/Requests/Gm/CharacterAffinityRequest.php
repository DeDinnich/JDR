<?php

namespace App\Http\Requests\Gm;

use App\Enums\RevealState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CharacterAffinityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        $maximumLevel = count(config('jdr.character.affinity_levels')) - 1;

        return [
            'affinity_level' => ['required', 'integer', 'min:0', 'max:'.$maximumLevel],
            'reveal_state' => ['required', Rule::enum(RevealState::class)],
            'gm_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
