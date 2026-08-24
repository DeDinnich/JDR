<?php

namespace App\Http\Requests\Gm;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SessionExtractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where('role', UserRole::Player->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Sélectionnez au moins un joueur à extraire.',
            'user_ids.min' => 'Sélectionnez au moins un joueur à extraire.',
            'user_ids.*.exists' => 'Un des joueurs sélectionnés n’est pas disponible.',
        ];
    }
}
