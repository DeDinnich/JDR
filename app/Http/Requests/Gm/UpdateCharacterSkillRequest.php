<?php

namespace App\Http\Requests\Gm;

use App\Enums\RevealState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCharacterSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'bonus' => ['required', 'integer', 'min:-50', 'max:50'],
            'player_bonus' => ['sometimes', 'required', 'integer', 'min:-50', 'max:50'],
            'reveal_state' => ['required', Rule::enum(RevealState::class)],
            'gm_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
