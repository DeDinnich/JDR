<?php

namespace App\Services\Campaign;

use App\Models\Character;
use App\Models\House;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Attribution des origines familiales.
 *
 * Deux parcours coexistent :
 *
 *  - les joueurs standards tirent au sort une grande maison encore libre ;
 *  - un compte nominatif (configuré dans jdr.campaign.special_origin) reçoit
 *    d'office une origine réservée et ne voit jamais le tirage.
 *
 * L'exclusivité est garantie par la base, pas par le front : le tirage se fait
 * dans une transaction qui verrouille les maisons candidates, si bien que deux
 * joueurs qui cliquent en même temps ne peuvent pas obtenir la même famille.
 */
class HouseAssignmentService
{
    /** Ce compte reçoit-il une origine réservée plutôt qu'un tirage ? */
    public function hasReservedOrigin(User $user): bool
    {
        $email = config('jdr.campaign.special_origin.email');

        return $email !== null
            && mb_strtolower($user->email) === mb_strtolower($email);
    }

    /** Origine réservée de ce compte, si le lore lui en prévoit une. */
    public function reservedHouseFor(User $user): ?House
    {
        if (! $this->hasReservedOrigin($user)) {
            return null;
        }

        return House::query()
            ->where('slug', config('jdr.campaign.special_origin.house_slug'))
            ->first();
    }

    /**
     * Maisons encore disponibles au tirage, dans l'ordre d'affichage.
     *
     * @return Collection<int, House>
     */
    public function availableHouses(): Collection
    {
        return House::query()
            ->assignable()
            ->whereDoesntHave('characters')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Attribue une origine au personnage et renvoie la maison obtenue.
     *
     * Le compte à origine réservée court-circuite le tirage ; pour les autres,
     * la maison est choisie au hasard parmi celles encore libres.
     */
    public function assign(Character $character, User $user): House
    {
        return DB::transaction(function () use ($character, $user) {
            $house = $this->hasReservedOrigin($user)
                ? $this->reservedHouseFor($user)
                : $this->drawLockedHouse();

            if (! $house instanceof House) {
                throw new RuntimeException("Aucune origine disponible n'a pu être attribuée.");
            }

            $character->forceFill(['house_id' => $house->getKey()])->save();

            return $house;
        });
    }

    /**
     * Les trois grandes maisons proposées au choix, prises ou non.
     *
     * On renvoie aussi les maisons déjà prises : elles doivent apparaître
     * grisées, pas disparaître — sinon la fenêtre se réorganise sous les yeux
     * du joueur au moment où quelqu'un d'autre choisit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function choosableHouses(): array
    {
        return House::query()
            ->assignable()
            ->withCount('characters')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (House $house) => [
                ...$house->publicPayload(),
                'is_taken' => $house->characters_count > 0,
            ])
            ->all();
    }

    /**
     * Attribue la maison explicitement choisie par le joueur.
     *
     * L'exclusivité ne repose pas sur le grisage affiché à l'écran : la ligne
     * est verrouillée le temps de la transaction, si bien que deux joueurs qui
     * cliquent sur la même maison au même instant ne peuvent pas l'obtenir
     * tous les deux. Le perdant reçoit false et rejoue son choix.
     */
    public function claim(Character $character, string $slug): ?House
    {
        return DB::transaction(function () use ($character, $slug) {
            $house = House::query()
                ->assignable()
                ->where('slug', $slug)
                ->lockForUpdate()
                ->first();

            // Maison inconnue, réservée, ou déjà prise entre-temps.
            if (! $house instanceof House || $house->isTaken()) {
                return null;
            }

            $character->forceFill(['house_id' => $house->getKey()])->save();

            return $house;
        });
    }

    /**
     * Tire une maison libre en la verrouillant le temps de la transaction.
     *
     * Le verrou porte sur les lignes candidates : un second tirage concurrent
     * attend puis constate que la maison est prise, ce qui rend l'unicité
     * réellement garantie côté serveur.
     */
    private function drawLockedHouse(): ?House
    {
        $candidates = House::query()
            ->assignable()
            ->whereDoesntHave('characters')
            ->lockForUpdate()
            ->get();

        return $candidates->isEmpty() ? null : $candidates->random();
    }
}
