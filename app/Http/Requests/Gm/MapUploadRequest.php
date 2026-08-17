<?php

namespace App\Http\Requests\Gm;

use App\Services\World\MapTileService;
use Illuminate\Foundation\Http\FormRequest;

class MapUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            // `image` s'appuie sur le type réel du fichier, pas sur son
            // extension : un .php renommé en .jpg est refusé ici.
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:12288'],
            'grid_columns' => ['required', 'integer', 'min:1', 'max:'.MapTileService::MAX_COLUMNS],
            'grid_rows' => ['required', 'integer', 'min:1', 'max:'.MapTileService::MAX_ROWS],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'L’image ne doit pas dépasser 12 Mo.',
            'image.mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
        ];
    }
}
