<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NpcInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'content' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(array_keys(config('jdr.campaign.npc_information_categories')))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
