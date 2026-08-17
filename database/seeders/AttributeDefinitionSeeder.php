<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use Illuminate\Database\Seeder;

/**
 * Les six caractéristiques principales, lues depuis la configuration.
 *
 * Idempotent : relancer le seeder après avoir ajouté une caractéristique dans
 * config('jdr.character.attributes') se contente de créer la nouvelle.
 */
class AttributeDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('jdr.character.attributes') as $index => $attribute) {
            AttributeDefinition::query()->updateOrCreate(
                ['code' => $attribute['code']],
                [
                    'name' => $attribute['name'],
                    'abbreviation' => $attribute['abbreviation'],
                    'description' => $attribute['description'],
                    'sort_order' => $index,
                ]
            );
        }
    }
}
