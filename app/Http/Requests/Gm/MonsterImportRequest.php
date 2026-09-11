<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;

class MonsterImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'json' => ['required', 'string', 'max:2000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'json.required' => 'Le payload JSON est obligatoire.',
            'json.max' => 'Le payload JSON ne doit pas dépasser 2 Mo.',
        ];
    }
}
