<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Objet géré par le joueur lui-même.
 *
 * Volontairement plus étroit que la version MJ : le joueur ne décide pas de la
 * visibilité d'un objet. Tout ce qu'il crée lui est visible, et il ne peut pas
 * rendre invisible un objet pour se le cacher à lui-même.
 */
class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->character()->exists() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'equipped' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            ...$this->safe()->only(['name', 'category', 'description', 'quantity']),
            'equipped' => $this->boolean('equipped'),
            'is_visible_to_player' => true,
        ];
    }
}
