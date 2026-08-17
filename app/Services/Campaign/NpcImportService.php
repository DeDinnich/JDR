<?php

namespace App\Services\Campaign;

use App\Models\House;
use App\Models\Location;
use App\Models\Npc;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use JsonException;

/**
 * Import et export de PNJ au format JSON.
 *
 * Pensé pour le copier-coller : le MJ demande des PNJ à une IA, colle le JSON
 * tel quel et voit immédiatement ce qui a été compris. Un JSON invalide ne
 * doit jamais produire une 500 — il produit un message lisible.
 *
 * Politique de doublon (V1) : un PNJ dont le nom complet existe déjà est
 * ignoré et signalé, jamais écrasé.
 */
class NpcImportService
{
    /**
     * Analyse le texte collé sans rien écrire en base.
     *
     * @return array{ok: bool, errors: array<int, string>, npcs: array<int, array<string, mixed>>, duplicates: array<int, string>}
     */
    public function analyse(string $json): array
    {
        try {
            $payload = json_decode(trim($json), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return $this->failure(['JSON invalide : '.$exception->getMessage()]);
        }

        if (! is_array($payload)) {
            return $this->failure(['Le JSON doit être un objet contenant une clé "npcs".']);
        }

        // On tolère aussi bien {"npcs": [...]} qu'un tableau nu.
        $rows = $payload['npcs'] ?? $payload;

        if (! is_array($rows) || $rows === []) {
            return $this->failure(['Aucun PNJ trouvé : attendu une liste sous la clé "npcs".']);
        }

        $errors = [];
        $valid = [];
        $duplicates = [];
        $existing = Npc::query()->pluck('name')->map(fn (string $name) => mb_strtolower($name))->all();

        foreach (array_values($rows) as $index => $row) {
            $position = $index + 1;

            if (! is_array($row)) {
                $errors[] = "PNJ #{$position} : entrée illisible.";

                continue;
            }

            $validator = Validator::make($row, $this->rules(), $this->messages());

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "PNJ #{$position} : {$message}";
                }

                continue;
            }

            $name = $this->fullName($row);

            // Doublon dans la base, ou deux fois dans le même collage.
            if (in_array(mb_strtolower($name), $existing, true)) {
                $duplicates[] = $name;

                continue;
            }

            $existing[] = mb_strtolower($name);
            $valid[] = $row;
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'npcs' => $valid,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * Importe réellement. Renvoie le compte-rendu affiché au MJ.
     *
     * @return array{ok: bool, errors: array<int, string>, imported: int, duplicates: array<int, string>}
     */
    public function import(string $json): array
    {
        $analysis = $this->analyse($json);

        if (! $analysis['ok']) {
            return [...$analysis, 'imported' => 0];
        }

        $houses = House::query()->pluck('id', 'slug');
        $locations = Location::query()->pluck('id', 'name');
        $imported = 0;

        DB::transaction(function () use ($analysis, $houses, $locations, &$imported) {
            foreach ($analysis['npcs'] as $row) {
                $npc = Npc::create([
                    'name' => $this->fullName($row),
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'] ?? null,
                    'nickname' => $row['nickname'] ?? null,
                    'title' => $row['title'] ?? null,
                    'age' => $row['age'] ?? null,
                    'gender' => $row['gender'] ?? null,
                    'race' => $row['race'] ?? null,
                    'profession' => $row['profession'] ?? null,
                    'role' => $row['role'] ?? ($row['profession'] ?? null),
                    'house_id' => isset($row['house']) ? ($houses[$row['house']] ?? null) : null,
                    'location_id' => isset($row['location']) ? ($locations[$row['location']] ?? null) : null,
                    'description' => $row['public_description'] ?? null,
                    'personality' => $row['personality'] ?? null,
                    'game_master_notes' => $row['gm_notes'] ?? null,
                    'status' => $this->normalise($row['status'] ?? null, config('jdr.campaign.npc_statuses'), 'vivant'),
                    'importance' => $this->normalise($row['importance'] ?? null, config('jdr.campaign.npc_importances'), 'secondaire'),
                    'tags' => $row['tags'] ?? null,
                ]);

                foreach (array_values($row['secrets'] ?? []) as $order => $secret) {
                    $npc->secrets()->create([
                        'title' => $secret['title'],
                        'content' => $secret['content'] ?? null,
                        'sort_order' => $order,
                    ]);
                }

                foreach (array_values($row['revealable_information'] ?? []) as $order => $information) {
                    $npc->informations()->create([
                        'title' => $information['title'],
                        'content' => $information['content'] ?? null,
                        'category' => $information['category'] ?? 'autre',
                        'sort_order' => $order,
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

    /**
     * Export réservé au MJ : inclut notes et secrets.
     *
     * @param  iterable<int, Npc>  $npcs
     * @return array<string, mixed>
     */
    public function export(iterable $npcs): array
    {
        $rows = [];

        foreach ($npcs as $npc) {
            $rows[] = [
                'first_name' => $npc->first_name ?? $npc->name,
                'last_name' => $npc->last_name,
                'nickname' => $npc->nickname,
                'title' => $npc->title,
                'age' => $npc->age,
                'gender' => $npc->gender,
                'race' => $npc->race,
                'profession' => $npc->profession,
                'house' => $npc->house?->slug,
                'location' => $npc->location?->name,
                'public_description' => $npc->description,
                'personality' => $npc->personality,
                'gm_notes' => $npc->game_master_notes,
                'status' => $npc->status?->value,
                'importance' => $npc->importance?->value,
                'tags' => $npc->tags,
                'secrets' => $npc->secrets->map(fn ($secret) => [
                    'title' => $secret->title,
                    'content' => $secret->content,
                ])->all(),
                'revealable_information' => $npc->informations->map(fn ($information) => [
                    'title' => $information->title,
                    'content' => $information->content,
                    'category' => $information->category,
                ])->all(),
            ];
        }

        return ['npcs' => $rows];
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'age' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'gender' => ['nullable', 'string', 'max:32'],
            'race' => ['nullable', 'string', 'max:64'],
            'profession' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'house' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:120'],
            'public_description' => ['nullable', 'string', 'max:5000'],
            'personality' => ['nullable', 'string', 'max:5000'],
            'gm_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', 'max:32'],
            'importance' => ['nullable', 'string', 'max:32'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
            'secrets' => ['nullable', 'array'],
            'secrets.*.title' => ['required', 'string', 'max:180'],
            'secrets.*.content' => ['nullable', 'string', 'max:5000'],
            'revealable_information' => ['nullable', 'array'],
            'revealable_information.*.title' => ['required', 'string', 'max:180'],
            'revealable_information.*.content' => ['nullable', 'string', 'max:5000'],
            'revealable_information.*.category' => ['nullable', 'string', 'max:32'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'first_name.required' => 'first_name est requis.',
            'secrets.*.title.required' => 'chaque secret doit avoir un title.',
            'revealable_information.*.title.required' => 'chaque information doit avoir un title.',
        ];
    }

    /** @param  array<string, mixed>  $row */
    private function fullName(array $row): string
    {
        return trim($row['first_name'].' '.($row['last_name'] ?? ''));
    }

    /**
     * Accepte indifféremment la clé interne ou un synonyme anglais courant
     * (« alive », « major »...), sans jamais laisser passer une valeur inconnue.
     *
     * @param  array<string, string>  $allowed
     */
    private function normalise(?string $value, array $allowed, string $fallback): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = mb_strtolower($value);

        if (isset($allowed[$value])) {
            return $value;
        }

        $synonyms = [
            'alive' => 'vivant', 'dead' => 'mort', 'missing' => 'disparu', 'unknown' => 'inconnu',
            'background' => 'figurant', 'secondary' => 'secondaire', 'major' => 'majeur', 'central' => 'central',
        ];

        return isset($synonyms[$value]) && isset($allowed[$synonyms[$value]])
            ? $synonyms[$value]
            : $fallback;
    }

    /** @return array{ok: bool, errors: array<int, string>, npcs: array<int, mixed>, duplicates: array<int, string>} */
    private function failure(array $errors): array
    {
        return ['ok' => false, 'errors' => $errors, 'npcs' => [], 'duplicates' => []];
    }
}
