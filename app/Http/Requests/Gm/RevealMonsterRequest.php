<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;

class RevealMonsterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'all_players' => ['nullable', 'boolean'],
            'user_ids' => ['required_unless:all_players,1', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /** @return array<int, int> */
    public function userIds(): array
    {
        return array_map('intval', $this->validated('user_ids', []));
    }
}
