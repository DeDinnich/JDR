<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Choix des joueurs destinataires d'une révélation.
 *
 * `user_ids` est toujours validé contre la table users ; le service refiltre
 * ensuite sur le rôle joueur, pour qu'un identifiant bricolé dans le formulaire
 * ne puisse viser ni le MJ ni un compte inexistant.
 */
class RevealNpcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'relationship' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<int, int> */
    public function userIds(): array
    {
        return array_map('intval', $this->validated()['user_ids']);
    }
}
