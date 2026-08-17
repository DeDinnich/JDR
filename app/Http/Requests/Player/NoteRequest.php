<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('note');

        return $note === null || $note->user_id === $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'content' => ['nullable', 'string', 'max:20000'],
            'pinned' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Balises autorisées dans une note.
     *
     * L'éditeur produit du HTML : on ne fait donc jamais confiance à ce qui
     * arrive. Tout ce qui n'est pas de la mise en forme simple est retiré —
     * en particulier <script>, <style>, <iframe> et les attributs d'événement,
     * qui sinon s'exécuteraient au réaffichage de la note.
     */
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><ul><ol><li><h2><h3><blockquote>';

    public function payload(): array
    {
        return [
            'title' => $this->safe()->string('title')->value(),
            'content' => $this->sanitise($this->safe()->string('content')->value()),
            'pinned' => $this->boolean('pinned'),
        ];
    }

    private function sanitise(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);

        // strip_tags conserve les attributs des balises autorisées : on retire
        // donc tout attribut, ce qui neutralise onclick, style et href.
        return preg_replace('/<(\w+)\b[^>]*>/i', '<$1>', $clean);
    }
}
