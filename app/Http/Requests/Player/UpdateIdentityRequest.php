<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Champs d'identité que le joueur tient à jour lui-même.
 *
 * Volontairement limité à ce qui relève de son personnage : ni PV, ni
 * caractéristiques, ni origine — la maison est posée une fois pour toutes au
 * moment de la naissance et ne se rejoue pas depuis cet écran.
 */
class UpdateIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->character()->exists() === true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:120'],
            'gender' => ['nullable', 'string', 'max:32'],
            'ancestry' => ['nullable', 'string', 'max:64'],
            'current_location' => ['nullable', 'string', 'max:120'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'adventurer_title' => ['nullable', 'string', 'max:120'],
            'background' => ['nullable', 'string', 'max:180'],
            'biography' => ['nullable', 'string', 'max:8000'],
            'traits' => ['nullable', 'string', 'max:4000'],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = $this->validated();

        // Le nom affiché suit le prénom et le nom, sans quoi la fiche et le
        // reste du site se désynchroniseraient.
        $data['name'] = trim($data['first_name'].' '.($data['last_name'] ?? ''));

        return $data;
    }
}
