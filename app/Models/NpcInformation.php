<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Information sur un PNJ que le MJ dévoile progressivement.
 *
 * Une même information peut être connue de certains joueurs seulement : la
 * connaissance vit dans le pivot npc_information_user, jamais sur la ligne.
 */
#[Fillable(['npc_id', 'title', 'content', 'category', 'sort_order'])]
class NpcInformation extends Model
{
    // L'inflecteur de Laravel traite « information » comme indénombrable et
    // en déduirait `npc_information` : on fixe le nom au pluriel réel.
    protected $table = 'npc_informations';

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function revealedTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'npc_information_user')
            ->withPivot('revealed_at');
    }

    public function categoryLabel(): string
    {
        return config('jdr.campaign.npc_information_categories')[$this->category] ?? $this->category;
    }
}
