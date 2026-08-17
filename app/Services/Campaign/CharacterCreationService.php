<?php

namespace App\Services\Campaign;

use App\Models\AttributeDefinition;
use App\Models\Character;
use App\Models\House;
use App\Models\Npc;
use App\Models\User;
use App\Services\CharacterSheet\CharacterSheetBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Parcours de création d'un personnage joueur.
 *
 * Le personnage naît bébé : le joueur ne renseigne qu'une identité minimale
 * (prénom, nom, genre) et ne choisit ni classe, ni caractéristiques, ni
 * compétences — tout cela se construira pendant l'enfance. La fiche complète
 * est immédiatement montée par CharacterSheetBuilder, entièrement cachée.
 */
class CharacterCreationService
{
    public function __construct(
        private readonly CharacterSheetBuilder $builder,
        private readonly HouseAssignmentService $houses,
    ) {}

    /**
     * Crée le nouveau-né et sa fiche, sans origine : la maison est tirée dans
     * un second temps par revealOrigin().
     *
     * @param  array<string, mixed>  $identity
     */
    public function create(User $user, array $identity): Character
    {
        return DB::transaction(function () use ($user, $identity) {
            $character = Character::create([
                'user_id' => $user->getKey(),
                'name' => trim($identity['first_name'].' '.($identity['last_name'] ?? '')),
                'first_name' => $identity['first_name'],
                'last_name' => $identity['last_name'] ?? null,
                'gender' => $identity['gender'] ?? null,
                // Le personnage commence à l'âge zéro : l'interface doit rester
                // lisible pour un nouveau-né sans classe ni profession.
                'age_years' => 0,
                'health' => 10,
                'max_health' => 10,
                'status' => 'En forme',
            ]);

            // Monte les 6 caractéristiques, les compétences et les affinités.
            $this->builder->initialize($character);

            $this->applyReservedOrigin($character, $user);

            return $character->refresh();
        });
    }

    /**
     * Pose d'office l'origine réservée, s'il y en a une pour ce compte.
     *
     * Appelée par les DEUX parcours qui créent un personnage — l'inscription
     * directe et le parcours de naissance — pour que le compte concerné ne
     * tombe jamais sur l'écran de choix des maisons, quelle que soit la porte
     * d'entrée. Renvoie null pour un joueur ordinaire, qui choisira lui-même.
     */
    public function applyReservedOrigin(Character $character, User $user): ?House
    {
        if (! $this->houses->hasReservedOrigin($user)) {
            return null;
        }

        $reserved = $this->houses->reservedHouseFor($user);

        if (! $reserved instanceof House) {
            return null;
        }

        $character->forceFill(['house_id' => $reserved->getKey()])->save();
        $this->settleInto($character, $reserved, $user);

        return $reserved;
    }

    /**
     * Attribue l'origine du personnage puis lui fait rencontrer ses parents.
     *
     * Renvoie la maison obtenue pour que le contrôleur puisse la mettre en
     * scène. L'unicité entre joueurs est garantie par HouseAssignmentService.
     */
    public function revealOrigin(Character $character, User $user): House
    {
        return DB::transaction(function () use ($character, $user) {
            $house = $this->houses->assign($character, $user);

            $this->settleInto($character, $house, $user);

            return $house;
        });
    }

    /**
     * Installe le personnage dans la maison qu'il vient de choisir.
     *
     * Renvoie null si la maison a été prise entre l'affichage de l'écran et le
     * clic : le joueur doit alors rejouer son choix.
     */
    public function chooseOrigin(Character $character, User $user, string $slug): ?House
    {
        return DB::transaction(function () use ($character, $user, $slug) {
            $house = $this->houses->claim($character, $slug);

            if (! $house instanceof House) {
                return null;
            }

            $this->settleInto($character, $house, $user);

            return $house;
        });
    }

    /** Stats de départ de la maison, puis rencontre des parents. */
    private function settleInto(Character $character, House $house, User $user): void
    {
        $this->applyHouseBaseStats($character, $house);
        $this->introduceParents($character, $house, $user);
    }

    /**
     * Applique les caractéristiques de départ liées à l'origine.
     *
     * Tout part au plancher — ce sont des enfants — sauf les deux
     * caractéristiques que la maison cultive. Les valeurs vivent dans
     * config('jdr.character.house_base_stats'), jamais en dur ici.
     */
    private function applyHouseBaseStats(Character $character, House $house): void
    {
        $stats = config('jdr.character.house_base_stats');
        $strengths = $stats['strengths'][$house->slug] ?? [];

        $definitions = AttributeDefinition::query()->pluck('id', 'code');

        foreach ($definitions as $code => $definitionId) {
            $character->attributes()
                ->where('attribute_definition_id', $definitionId)
                ->update([
                    'value' => in_array($code, $strengths, true) ? $stats['bonus'] : $stats['base'],
                ]);
        }
    }

    /**
     * Rattache les parents de la maison à l'enfant et les fait découvrir au
     * joueur, avec les seules informations de catégorie « relation » : le
     * nouveau-né reconnaît ses parents, rien de plus.
     */
    private function introduceParents(Character $character, House $house, User $user): void
    {
        $parents = Npc::query()
            ->where('house_id', $house->getKey())
            ->whereIn('family_role', ['pere', 'mere'])
            ->with('informations')
            ->get();

        foreach ($parents as $parent) {
            $character->relatives()->syncWithoutDetaching([
                $parent->getKey() => ['relation' => $parent->family_role],
            ]);

            // `relationship` reste vide : c'est le classement personnel du
            // joueur. Le lien familial lui est transmis par l'information de
            // catégorie « relation » ouverte juste en dessous.
            $parent->discoveredBy()->syncWithoutDetaching([
                $user->getKey() => ['discovered_at' => now()],
            ]);

            // Seules les informations de lien familial sont ouvertes ici. Les
            // fonctions, rumeurs et secrets restent fermés jusqu'à ce que le MJ
            // décide de les révéler.
            $parent->informations
                ->where('category', 'relation')
                ->each(fn ($information) => $information->revealedTo()->syncWithoutDetaching([
                    $user->getKey() => ['revealed_at' => now()],
                ]));
        }
    }
}
