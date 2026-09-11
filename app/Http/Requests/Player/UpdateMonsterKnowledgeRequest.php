<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonsterKnowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->discoveredMonsters()
            ->where('monsters.id', $this->route('monster')->id)
            ->exists() === true;
    }

    public function rules(): array
    {
        return [
            'known_health' => ['nullable', 'string', 'max:255'],
            'known_mana' => ['nullable', 'string', 'max:255'],
            'known_abilities' => ['nullable', 'string', 'max:10000'],
            'known_damage' => ['nullable', 'string', 'max:255'],
            'known_weakness' => ['nullable', 'string', 'max:5000'],
            'personal_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
