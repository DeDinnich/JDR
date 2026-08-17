<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        $mapId = $this->route('map')?->id;

        return [
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('maps', 'slug')->ignore($mapId)],
            'description' => ['nullable', 'string', 'max:3000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function payload(): array
    {
        return [
            ...$this->safe()->only(['title', 'slug', 'description', 'image_path', 'sort_order']),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
