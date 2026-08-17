<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNpcNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->discoveredNpcs()->where('npcs.id', $this->route('npc')->id)->exists() === true;
    }

    /**
     * Mise en forme autorisée, identique au journal : l'éditeur produit du
     * HTML, on ne lui fait donc jamais confiance.
     */
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><ul><ol><li><h2><h3><blockquote>';

    public function rules(): array
    {
        return [
            // La relation devient facultative : l'éditeur enregistre en
            // arrière-plan et n'envoie parfois que le contenu.
            'relationship' => ['nullable', Rule::in(['allie', 'neutre', 'mefiance', 'ennemi'])],
            'personal_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = ['personal_notes' => $this->sanitise($this->input('personal_notes'))];

        if ($this->filled('relationship')) {
            $data['relationship'] = $this->validated()['relationship'];
        }

        return $data;
    }

    private function sanitise(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);

        // strip_tags garde les attributs des balises autorisées : on les retire
        // tous, ce qui neutralise onclick, style et href.
        return preg_replace('/<(\\w+)\\b[^>]*>/i', '<$1>', $clean);
    }
}
