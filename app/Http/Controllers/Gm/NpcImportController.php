<?php

namespace App\Http\Controllers\Gm;

use App\Http\Controllers\Controller;
use App\Services\Campaign\NpcImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Import de PNJ par copier-coller de JSON.
 *
 * Deux temps volontaires : « Analyser » ne touche pas la base et affiche ce qui
 * a été compris, « Importer » écrit. Un JSON invalide produit toujours un
 * message lisible, jamais une erreur 500.
 */
class NpcImportController extends Controller
{
    public function show(): View
    {
        return view('gm.npcs.import', [
            'result' => null,
            'json' => '',
            'example' => $this->example(),
        ]);
    }

    public function analyse(Request $request, NpcImportService $service): View
    {
        $json = (string) $request->input('json');

        return view('gm.npcs.import', [
            'result' => ['mode' => 'analyse'] + $service->analyse($json),
            'json' => $json,
            'example' => $this->example(),
        ]);
    }

    public function store(Request $request, NpcImportService $service): View|RedirectResponse
    {
        $json = (string) $request->input('json');
        $result = $service->import($json);

        if (! $result['ok']) {
            return view('gm.npcs.import', [
                'result' => ['mode' => 'import'] + $result,
                'json' => $json,
                'example' => $this->example(),
            ]);
        }

        $message = $result['imported'].' PNJ importé(s).';

        if ($result['duplicates'] !== []) {
            $message .= ' '.count($result['duplicates']).' ignoré(s) car déjà existant(s) : '
                .implode(', ', $result['duplicates']).'.';
        }

        return redirect()->route('gm.npcs.index')->with('success', $message);
    }

    /** Exemple affiché dans l'accordéon « Voir le format attendu ». */
    private function example(): string
    {
        return json_encode([
            'npcs' => [[
                'first_name' => 'Aldric',
                'last_name' => 'Valtheris',
                'nickname' => null,
                'title' => 'Lord',
                'age' => 31,
                'gender' => 'Homme',
                'race' => 'Humain',
                'profession' => 'Commandant royal',
                'house' => 'valtheris',
                'location' => "Château royal d'Ashura",
                'public_description' => 'Un homme austère au maintien militaire.',
                'personality' => 'Discipliné, réservé, attaché à son devoir.',
                'gm_notes' => 'Il doute de la stratégie militaire du royaume.',
                'status' => 'vivant',
                'importance' => 'majeur',
                'tags' => ['noble', 'armée', 'parent'],
                'secrets' => [[
                    'title' => 'Doutes sur la guerre',
                    'content' => 'Aldric pense que le commandement mène Ashura à la défaite.',
                ]],
                'revealable_information' => [[
                    'title' => 'Fonction',
                    'content' => 'Il commande une partie des forces royales.',
                    'category' => 'profession',
                ]],
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
