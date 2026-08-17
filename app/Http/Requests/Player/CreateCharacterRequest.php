<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Identité minimale d'un nouveau-né.
 *
 * Volontairement pauvre : aucune classe, aucune caractéristique, aucune
 * compétence n'est demandée au joueur — tout cela se développera en jeu.
 */
class CreateCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Un joueur ne peut créer qu'un seul personnage.
        return $this->user() !== null && ! $this->user()->character()->exists();
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'gender' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'gender' => 'genre',
        ];
    }
}
