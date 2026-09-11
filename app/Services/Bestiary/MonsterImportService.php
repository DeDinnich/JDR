<?php

namespace App\Services\Bestiary;

use App\Models\Monster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use JsonException;

class MonsterImportService
{
    /** @return array{ok: bool, errors: array<int, string>, monsters: array<int, array<string, mixed>>, duplicates: array<int, string>} */
    public function analyse(string $json): array
    {
        try {
            $payload = json_decode(trim($json), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return $this->failure(['JSON invalide : '.$exception->getMessage()]);
        }

        if (! is_array($payload)) {
            return $this->failure(['Le JSON doit contenir une liste sous la clé "monsters".']);
        }

        $rows = $payload['monsters'] ?? $payload;

        if (! is_array($rows) || $rows === []) {
            return $this->failure(['Aucun monstre trouvé sous la clé "monsters".']);
        }

        if (count($rows) > 500) {
            return $this->failure(['Un import est limité à 500 monstres.']);
        }

        $errors = [];
        $valid = [];
        $duplicates = [];
        $existingNames = Monster::query()->pluck('name')->map(fn (string $name) => mb_strtolower($name))->all();

        foreach (array_values($rows) as $index => $row) {
            $position = $index + 1;

            if (! is_array($row)) {
                $errors[] = "Monstre #{$position} : entrée illisible.";

                continue;
            }

            $validator = Validator::make($row, $this->rules(), $this->messages());

            $validator->after(function ($validator) use ($row): void {
                if (($row['health']['current'] ?? 0) > ($row['health']['max'] ?? PHP_INT_MAX)) {
                    $validator->errors()->add('health.current', 'la vie actuelle ne peut pas dépasser la vie maximale.');
                }

                if (($row['mana']['current'] ?? 0) > ($row['mana']['max'] ?? PHP_INT_MAX)) {
                    $validator->errors()->add('mana.current', 'le mana actuel ne peut pas dépasser le mana maximal.');
                }
            });

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Monstre #{$position} : {$message}";
                }

                continue;
            }

            $name = trim((string) $row['name']);

            if (in_array(mb_strtolower($name), $existingNames, true)) {
                $duplicates[] = $name;

                continue;
            }

            $existingNames[] = mb_strtolower($name);
            $valid[] = $row;
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'monsters' => $valid,
            'duplicates' => $duplicates,
        ];
    }

    /** @return array{ok: bool, errors: array<int, string>, imported: int, duplicates: array<int, string>} */
    public function import(string $json): array
    {
        $analysis = $this->analyse($json);

        if (! $analysis['ok']) {
            return [...$analysis, 'imported' => 0];
        }

        $imported = 0;

        DB::transaction(function () use ($analysis, &$imported): void {
            foreach ($analysis['monsters'] as $row) {
                $monster = Monster::create($this->monsterPayload($row));

                foreach (array_values($row['abilities'] ?? []) as $position => $ability) {
                    $monster->abilities()->create([
                        'name' => $ability['name'],
                        'type' => $ability['type'] ?? null,
                        'description' => $ability['description'] ?? null,
                        'dice_formula' => isset($ability['dice_formula']) ? strtolower($ability['dice_formula']) : null,
                        'mana_cost' => $ability['mana_cost'] ?? 0,
                        'cooldown' => $ability['cooldown'] ?? null,
                        'sort_order' => $position,
                    ]);
                }

                $imported++;
            }
        });

        return [
            'ok' => true,
            'errors' => [],
            'imported' => $imported,
            'duplicates' => $analysis['duplicates'],
        ];
    }

    /** @param iterable<int, Monster> $monsters */
    public function export(iterable $monsters): array
    {
        return [
            'schema_version' => '1.0',
            'monsters' => collect($monsters)->map(fn (Monster $monster) => [
                'name' => $monster->name,
                'type' => $monster->type,
                'size' => $monster->size,
                'threat_level' => $monster->threat_level,
                'public_description' => $monster->description,
                'habitat' => $monster->habitat,
                'behavior' => $monster->behavior,
                'gm_notes' => $monster->game_master_notes,
                'health' => ['current' => $monster->health_current, 'max' => $monster->health_max],
                'mana' => ['current' => $monster->mana_current, 'max' => $monster->mana_max],
                'armor' => $monster->armor,
                'stats' => [
                    'strength' => $monster->strength,
                    'endurance' => $monster->endurance,
                    'dexterity' => $monster->dexterity,
                    'intelligence' => $monster->intelligence,
                    'willpower' => $monster->willpower,
                    'perception' => $monster->perception,
                ],
                'damage_dice' => $monster->damage_dice,
                'weakness' => $monster->weakness,
                'abilities' => $monster->abilities->map(fn ($ability) => [
                    'name' => $ability->name,
                    'type' => $ability->type,
                    'description' => $ability->description,
                    'dice_formula' => $ability->dice_formula,
                    'mana_cost' => $ability->mana_cost,
                    'cooldown' => $ability->cooldown,
                ])->all(),
            ])->values()->all(),
        ];
    }

    public function example(): string
    {
        return json_encode([
            'schema_version' => '1.0',
            'monsters' => [[
                'name' => "Loup d'ombre",
                'type' => 'Bête magique',
                'size' => 'Moyenne',
                'threat_level' => 'Dangereux',
                'public_description' => 'Une silhouette lupine dont le pelage absorbe la lumière.',
                'habitat' => 'Forêts anciennes et ruines',
                'behavior' => 'Chasse en meute et contourne les cibles isolées.',
                'gm_notes' => 'Fuit si le meneur tombe.',
                'health' => ['current' => 38, 'max' => 38],
                'mana' => ['current' => 12, 'max' => 12],
                'armor' => 3,
                'stats' => [
                    'strength' => 12,
                    'endurance' => 11,
                    'dexterity' => 15,
                    'intelligence' => 5,
                    'willpower' => 8,
                    'perception' => 14,
                ],
                'damage_dice' => '2d6+2',
                'weakness' => 'La lumière vive dissipe son camouflage.',
                'abilities' => [[
                    'name' => 'Morsure crépusculaire',
                    'type' => 'Attaque',
                    'description' => 'Bondit depuis une zone sombre.',
                    'dice_formula' => '2d8+3',
                    'mana_cost' => 0,
                    'cooldown' => null,
                ]],
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return array<string, mixed> */
    private function monsterPayload(array $row): array
    {
        return [
            'name' => trim($row['name']),
            'type' => $row['type'] ?? null,
            'size' => $row['size'] ?? null,
            'threat_level' => $row['threat_level'] ?? null,
            'description' => $row['public_description'] ?? null,
            'habitat' => $row['habitat'] ?? null,
            'behavior' => $row['behavior'] ?? null,
            'game_master_notes' => $row['gm_notes'] ?? null,
            'health_current' => $row['health']['current'] ?? ($row['health']['max'] ?? 1),
            'health_max' => $row['health']['max'] ?? 1,
            'mana_current' => $row['mana']['current'] ?? ($row['mana']['max'] ?? 0),
            'mana_max' => $row['mana']['max'] ?? 0,
            'armor' => $row['armor'] ?? 0,
            'strength' => $row['stats']['strength'] ?? 0,
            'endurance' => $row['stats']['endurance'] ?? 0,
            'dexterity' => $row['stats']['dexterity'] ?? 0,
            'intelligence' => $row['stats']['intelligence'] ?? 0,
            'willpower' => $row['stats']['willpower'] ?? 0,
            'perception' => $row['stats']['perception'] ?? 0,
            'damage_dice' => isset($row['damage_dice']) ? strtolower($row['damage_dice']) : null,
            'weakness' => $row['weakness'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        $diceRule = 'regex:'.DiceRoller::FORMULA_PATTERN;

        return [
            'name' => ['required', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'string', 'max:64'],
            'threat_level' => ['nullable', 'string', 'max:64'],
            'public_description' => ['nullable', 'string', 'max:5000'],
            'habitat' => ['nullable', 'string', 'max:255'],
            'behavior' => ['nullable', 'string', 'max:5000'],
            'gm_notes' => ['nullable', 'string', 'max:5000'],
            'health' => ['nullable', 'array'],
            'health.current' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'health.max' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'mana' => ['nullable', 'array'],
            'mana.current' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'mana.max' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'armor' => ['nullable', 'integer', 'min:0', 'max:999'],
            'stats' => ['nullable', 'array'],
            'stats.strength' => ['nullable', 'integer', 'min:0', 'max:999'],
            'stats.endurance' => ['nullable', 'integer', 'min:0', 'max:999'],
            'stats.dexterity' => ['nullable', 'integer', 'min:0', 'max:999'],
            'stats.intelligence' => ['nullable', 'integer', 'min:0', 'max:999'],
            'stats.willpower' => ['nullable', 'integer', 'min:0', 'max:999'],
            'stats.perception' => ['nullable', 'integer', 'min:0', 'max:999'],
            'damage_dice' => ['nullable', 'string', $diceRule],
            'weakness' => ['nullable', 'string', 'max:5000'],
            'abilities' => ['nullable', 'array', 'max:100'],
            'abilities.*.name' => ['required', 'string', 'max:180'],
            'abilities.*.type' => ['nullable', 'string', 'max:64'],
            'abilities.*.description' => ['nullable', 'string', 'max:5000'],
            'abilities.*.dice_formula' => ['nullable', 'string', $diceRule],
            'abilities.*.mana_cost' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'abilities.*.cooldown' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'name est requis.',
            'damage_dice.regex' => 'damage_dice doit suivre le format 2d6+3.',
            'abilities.*.name.required' => 'chaque capacité doit avoir un nom.',
            'abilities.*.dice_formula.regex' => 'une formule de capacité doit suivre le format 2d6+3.',
        ];
    }

    /** @return array{ok: false, errors: array<int, string>, monsters: array<int, never>, duplicates: array<int, never>} */
    private function failure(array $errors): array
    {
        return ['ok' => false, 'errors' => $errors, 'monsters' => [], 'duplicates' => []];
    }
}
