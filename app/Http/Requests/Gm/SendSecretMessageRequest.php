<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendSecretMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'player')],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
