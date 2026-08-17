<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            // Identité — un nouveau-né n'a presque rien de défini.
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'portrait_path' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date'],
            'age_years' => ['required', 'integer', 'min:0', 'max:999'],
            'ancestry' => ['nullable', 'string', 'max:100'],
            'origin' => ['nullable', 'string', 'max:150'],
            'current_location' => ['nullable', 'string', 'max:150'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'archetype' => ['nullable', 'string', 'max:100'],
            'adventurer_title' => ['nullable', 'string', 'max:100'],
            'background' => ['nullable', 'string', 'max:150'],

            // Ressources.
            'health' => ['required', 'integer', 'min:0', 'lte:max_health'],
            'max_health' => ['required', 'integer', 'min:1', 'max:9999'],
            'mana_current' => ['required', 'integer', 'min:0', 'max:9999'],
            // Laisser vide = mana maximum dérivé de MAN par la formule.
            'mana_max' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'armor' => ['required', 'integer', 'min:0', 'max:999'],
            'gold' => ['required', 'integer', 'min:0', 'max:999999999'],
            'status' => ['required', 'string', 'max:100'],
            'current_map_id' => ['nullable', 'integer', 'exists:maps,id'],
            'biography' => ['nullable', 'string', 'max:5000'],
            'traits' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** Le nom affiché reste synchronisé avec l'identité saisie. */
    public function payload(): array
    {
        $data = $this->validated();
        $data['name'] = trim($data['first_name'].' '.($data['last_name'] ?? ''));

        return $data;
    }
}
