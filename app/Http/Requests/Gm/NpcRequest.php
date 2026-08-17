<?php

namespace App\Http\Requests\Gm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NpcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGameMaster() === true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'age' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'gender' => ['nullable', 'string', 'max:32'],
            'race' => ['nullable', 'string', 'max:64'],
            'profession' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'house_id' => ['nullable', 'integer', 'exists:houses,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'personality' => ['nullable', 'string', 'max:5000'],
            'game_master_notes' => ['nullable', 'string', 'max:5000'],
            'portrait_path' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(config('jdr.campaign.npc_statuses')))],
            'importance' => ['required', Rule::in(array_keys(config('jdr.campaign.npc_importances')))],
            'tags' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = $this->safe()->except('tags');

        // Les tags arrivent en texte libre séparé par des virgules.
        $data['tags'] = collect(explode(',', (string) $this->input('tags')))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->values()
            ->all() ?: null;

        $data['name'] = trim($data['first_name'].' '.($data['last_name'] ?? ''));

        return $data;
    }
}
