<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', 'min:0', 'max:'.config('jdr.character.attribute_max')],
            'modifier' => ['required', 'integer', 'min:-20', 'max:20'],
        ];
    }
}
