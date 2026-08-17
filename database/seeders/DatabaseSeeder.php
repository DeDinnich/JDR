<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $email = config('jdr.admin.email');
        $password = config('jdr.admin.password');

        if (blank($email) || blank($password)) {
            throw new RuntimeException('ADMIN_MAIL et ADMIN_PASSWORD doivent être définis avant le seed.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Maître du jeu',
                'password' => $password,
                'role' => UserRole::GameMaster,
                'email_verified_at' => now(),
            ]
        );

        // Catalogue de la campagne : idempotent, rejouable à volonté après
        // l'ajout d'une compétence, d'une maîtrise ou d'une école de magie.
        $this->call([
            AttributeDefinitionSeeder::class,
            MagicSchoolSeeder::class,
            SkillDefinitionSeeder::class,
            MasteryDefinitionSeeder::class,
            AbilityDefinitionSeeder::class,
            HouseSeeder::class,
            CampaignNpcSeeder::class,
        ]);

        // Le personnage de démonstration n'est pas inclus par défaut : il crée
        // un compte joueur. Le jouer explicitement avec
        // php artisan db:seed --class=DemoCharacterSeeder
    }
}
