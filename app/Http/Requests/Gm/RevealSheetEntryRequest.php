<?php

namespace App\Http\Requests\Gm;

use App\Enums\RevealState;
use App\Services\CharacterSheet\CharacterRevealService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevealSheetEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(CharacterRevealService::types())],
            'id' => ['required', 'integer'],
            'state' => ['required', Rule::enum(RevealState::class)],
        ];
    }
}
