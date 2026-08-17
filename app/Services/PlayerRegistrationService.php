<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Campaign\CharacterCreationService;
use App\Services\CharacterSheet\CharacterSheetBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Inscription d'un joueur et naissance de son personnage.
 *
 * Le personnage arrive au monde sans rien de déterminé : pas de classe, pas de
 * profession, pas d'origine. La fiche est montée en intégralité, puis le
 * joueur choisit sa grande maison dans une fenêtre dont il ne peut pas sortir
 * (middleware EnsureOriginChosen).
 */
class PlayerRegistrationService
{
    public function __construct(
        private readonly CharacterSheetBuilder $sheet,
        private readonly CharacterCreationService $creation,
    ) {}

    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['character_name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Player,
            ]);

            $character = $user->character()->create([
                'name' => $data['character_name'],
                'first_name' => $data['character_name'],
                'age_years' => 0,
                'health' => 6,
                'max_health' => 6,
                'mana_current' => 0,
                'status' => 'Nouveau-né',
            ]);

            $this->sheet->initialize($character);

            // Le personnage naît sans maison : le joueur la choisit dans la
            // fenêtre bloquante qui suit. Seul le compte à origine réservée
            // reçoit la sienne d'office et saute donc cet écran.
            $this->creation->applyReservedOrigin($character, $user);

            return $user->load('character.attributes');
        });
    }
}
