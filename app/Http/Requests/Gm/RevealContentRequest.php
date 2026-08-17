<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevealContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['individual', 'all'])],
            'user_id' => [
                Rule::requiredIf($this->input('scope') === 'individual'),
                'nullable', 'integer', Rule::exists('users', 'id')->where('role', 'player'),
            ],
        ];
    }
}
