<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortraitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->character()->exists() === true;
    }

    public function rules(): array
    {
        return [
            // `image` valide le type réel du fichier, pas son extension : un
            // script renommé en .jpg est refusé ici.
            'portrait' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'portrait.max' => 'Le portrait ne doit pas dépasser 4 Mo.',
            'portrait.mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
        ];
    }
}
