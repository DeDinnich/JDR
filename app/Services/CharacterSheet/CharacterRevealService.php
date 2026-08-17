<?php

namespace App\Services\CharacterSheet;

use App\Contracts\RevealableSheetEntry;
use App\Enums\RevealState;
use App\Events\CharacterSheetRevealed;
use App\Models\Character;
use App\Models\CharacterAbility;
use App\Models\CharacterAffinity;
use App\Models\CharacterMastery;
use App\Models\CharacterSkill;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Révélation progressive des données de la fiche.
 *
 * Le MJ ouvre les informations une par une, au rythme du récit : une compétence
 * spéciale une fois découverte, une affinité après un premier contact avec la
 * magie, une maîtrise après un entraînement. Chaque ouverture est poussée en
 * temps réel vers le joueur concerné.
 *
 * Les caractéristiques principales ne figurent volontairement pas ici : elles
 * sont toujours visibles par le joueur.
 */
class CharacterRevealService
{
    /** Types révélables adressables depuis l'interface MJ. */
    private const TYPES = [
        'skill' => CharacterSkill::class,
        'mastery' => CharacterMastery::class,
        'affinity' => CharacterAffinity::class,
        'ability' => CharacterAbility::class,
    ];

    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * Retrouve un élément révélable à partir du couple (type, id) envoyé par
     * l'interface MJ, en vérifiant qu'il appartient bien au personnage visé.
     *
     * Ce contrôle d'appartenance est volontairement centralisé ici : aucune
     * route de révélation ne peut l'oublier.
     */
    public function resolve(Character $character, string $type, int $id): RevealableSheetEntry
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        /** @var RevealableSheetEntry&Model $entry */
        $entry = self::TYPES[$type]::query()
            ->where('character_id', $character->id)
            ->findOrFail($id);

        return $entry;
    }

    /** Applique un état de révélation à un élément de fiche. */
    public function apply(RevealableSheetEntry $entry, RevealState $state, ?User $author = null): RevealableSheetEntry
    {
        $previous = $entry->getRevealState();

        if ($previous === $state) {
            return $entry;
        }

        $entry->setRevealState($state)->save();

        // On ne notifie que les ouvertures : refermer une donnée est une
        // correction du MJ, pas un événement de jeu.
        if ($state->isDiscovered() && ! $previous->isDiscovered() || $state === RevealState::Revealed) {
            $this->announce($entry);
        }

        return $entry;
    }

    /** Passe l'élément à l'étape de révélation suivante. */
    public function advance(RevealableSheetEntry $entry, ?User $author = null): RevealableSheetEntry
    {
        return $this->apply($entry, $entry->getRevealState()->nextStep(), $author);
    }

    public function hide(RevealableSheetEntry $entry, ?User $author = null): RevealableSheetEntry
    {
        return $this->apply($entry, RevealState::Hidden, $author);
    }

    /** Diffuse la révélation au joueur concerné, en temps réel. */
    private function announce(RevealableSheetEntry $entry): void
    {
        CharacterSheetRevealed::dispatch(
            $entry->getCharacter(),
            $entry->revealKind(),
            $entry->revealHeadline(),
            $entry->revealDescription(),
        );
    }
}
