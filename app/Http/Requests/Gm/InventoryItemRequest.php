<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;

class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'equipped' => ['nullable', 'boolean'],
            'is_visible_to_player' => ['nullable', 'boolean'],
        ];
    }

    public function payload(): array
    {
        return [
            ...$this->safe()->only(['name', 'category', 'description', 'quantity']),
            'equipped' => $this->boolean('equipped'),
            'is_visible_to_player' => $this->boolean('is_visible_to_player'),
        ];
    }
}
