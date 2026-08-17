<?php

namespace App\Services\Campaign;

use App\Enums\UserRole;
use App\Events\NpcRevealed;
use App\Models\Npc;
use App\Models\NpcInformation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Révélation d'un PNJ, et de ce que l'on en sait, joueur par joueur.
 *
 * Deux joueurs peuvent connaître le même PNJ à des degrés très différents :
 * la connaissance vit donc dans des pivots (npc_user, npc_information_user) et
 * jamais sur la ligne du PNJ. Rien n'est diffusé à la cantonade.
 */
class NpcRevealService
{
    /**
     * Joueurs auxquels le MJ peut adresser une révélation.
     *
     * @return Collection<int, User>
     */
    public function revealableAudience(): Collection
    {
        return User::query()
            ->where('role', UserRole::Player->value)
            ->orderBy('name')
            ->get();
    }

    /**
     * Fait découvrir le PNJ aux joueurs visés.
     *
     * Idempotent : re-révéler à un joueur qui connaît déjà le PNJ ne duplique
     * rien et ne le renotifie pas.
     *
     * @param  array<int, int>  $userIds
     * @return int nombre de joueurs pour lesquels c'est une découverte
     */
    public function revealNpc(Npc $npc, array $userIds, ?string $relationship = null): int
    {
        $users = $this->resolvePlayers($userIds);
        $alreadyKnown = $npc->discoveredBy()->pluck('users.id')->all();
        $discovered = 0;

        // Le pivot `relationship` appartient au joueur : c'est SON classement
        // (allié, méfiance...). Le MJ n'y touche pas ; la relation narrative
        // qu'il veut afficher passe par une information de catégorie
        // « relation », révélée en même temps.
        DB::transaction(function () use ($npc, $users, $alreadyKnown, &$discovered) {
            foreach ($users as $user) {
                if (in_array($user->id, $alreadyKnown, true)) {
                    continue;
                }

                $npc->discoveredBy()->attach($user->id, ['discovered_at' => now()]);

                $discovered++;
            }
        });

        // La notification part hors transaction : un échec de diffusion ne doit
        // pas annuler une découverte déjà acquise en base.
        foreach ($users as $user) {
            if (in_array($user->id, $alreadyKnown, true)) {
                continue;
            }

            NpcRevealed::dispatch(
                $user->id,
                'Nouvelle rencontre',
                $npc->fullName(),
                $relationship ?: ($npc->description ?: 'Tu fais sa connaissance.'),
                route('player.glossary.show', $npc),
                'Voir dans le glossaire',
            );
        }

        return $discovered;
    }

    /**
     * Ouvre une information précise à des joueurs.
     *
     * Un joueur qui ne connaît pas encore le PNJ le découvre au passage : on
     * évite ainsi une information orpheline dans son glossaire.
     *
     * @param  array<int, int>  $userIds
     * @return int nombre de joueurs pour lesquels l'information est nouvelle
     */
    public function revealInformation(NpcInformation $information, array $userIds): int
    {
        $npc = $information->npc;
        $users = $this->resolvePlayers($userIds);
        $alreadyInformed = $information->revealedTo()->pluck('users.id')->all();
        $revealed = 0;

        foreach ($users as $user) {
            if (in_array($user->id, $alreadyInformed, true)) {
                continue;
            }

            $this->revealNpc($npc, [$user->id]);

            $information->revealedTo()->attach($user->id, ['revealed_at' => now()]);
            $revealed++;

            NpcRevealed::dispatch(
                $user->id,
                'Nouvelle information',
                $npc->fullName(),
                $information->content ?: $information->title,
                route('player.glossary.show', $npc),
                'Voir dans le glossaire',
            );
        }

        return $revealed;
    }

    /**
     * Ne retient que des comptes joueurs existants : un identifiant fabriqué à
     * la main dans le formulaire ne peut pas viser le MJ ou un compte inconnu.
     *
     * @param  array<int, int>  $userIds
     * @return Collection<int, User>
     */
    private function resolvePlayers(array $userIds): Collection
    {
        return User::query()
            ->whereIn('id', $userIds)
            ->where('role', UserRole::Player->value)
            ->get();
    }
}
