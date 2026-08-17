<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;

class CharacterStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:8'],
            'duration_label' => ['nullable', 'string', 'max:64'],
            'visible_to_player' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            // Modificateurs saisis par code de caractéristique : modifiers[for] = -1
            'modifiers' => ['nullable', 'array'],
            'modifiers.*' => ['nullable', 'integer', 'min:-20', 'max:20'],
        ];
    }

    public function payload(): array
    {
        $modifiers = collect($this->validated()['modifiers'] ?? [])
            ->filter(fn ($value) => $value !== null && $value !== 0)
            ->all();

        return [
            ...collect($this->validated())->except(['modifiers', 'visible_to_player', 'is_active'])->all(),
            'modifiers' => $modifiers ?: null,
            'visible_to_player' => $this->boolean('visible_to_player'),
            'is_active' => $this->boolean('is_active', true),
        ];
    }
}
