<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNpcNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->discoveredNpcs()->where('npcs.id', $this->route('npc')->id)->exists() === true;
    }

    public function rules(): array
    {
        return [
            'relationship' => ['required', Rule::in(['allie', 'neutre', 'mefiance', 'ennemi'])],
            'personal_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
