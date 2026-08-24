<?php

namespace App\Http\Controllers\Gm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gm\NpcInformationRequest;
use App\Http\Requests\Gm\NpcRequest;
use App\Http\Requests\Gm\NpcSecretRequest;
use App\Http\Requests\Gm\RevealNpcRequest;
use App\Models\House;
use App\Models\Npc;
use App\Models\NpcInformation;
use App\Models\NpcSecret;
use App\Services\Campaign\NpcImportService;
use App\Services\Campaign\NpcRevealService;
use App\Services\NpcPortraitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Base de PNJ côté MJ.
 *
 * Conçue pour être utilisée pendant une partie : une liste filtrable, une fiche
 * unique où tout se modifie sur place, et une action de révélation à portée de
 * clic. Aucune donnée de cette section n'est accessible à un joueur — le groupe
 * de routes entier est derrière le middleware `role:game_master`.
 */
class NpcController extends Controller
{
    public function index(Request $request, NpcRevealService $reveal): View
    {
        $search = trim((string) $request->query('recherche'));

        $npcs = Npc::query()
            ->with('house', 'discoveredBy')
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('profession', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            }))
            ->when($request->filled('maison'), fn ($query) => $query->whereHas(
                'house', fn ($inner) => $inner->where('slug', $request->query('maison'))
            ))
            ->when($request->filled('importance'), fn ($query) => $query->where('importance', $request->query('importance')))
            ->when($request->filled('statut'), fn ($query) => $query->where('status', $request->query('statut')))
            ->orderBy('name')
            ->get();

        return view('gm.npcs.index', [
            'npcs' => $npcs,
            'players' => $reveal->revealableAudience(),
            'houses' => House::query()->orderBy('sort_order')->get(),
            'search' => $search,
            'filters' => $request->only(['maison', 'importance', 'statut']),
            'importances' => config('jdr.campaign.npc_importances'),
            'statuses' => config('jdr.campaign.npc_statuses'),
        ]);
    }

    public function show(Npc $npc, NpcRevealService $reveal): View
    {
        $npc->load('house', 'secrets', 'informations.revealedTo', 'discoveredBy');

        return view('gm.npcs.show', [
            'npc' => $npc,
            'houses' => House::query()->orderBy('sort_order')->get(),
            'players' => $reveal->revealableAudience(),
            'importances' => config('jdr.campaign.npc_importances'),
            'statuses' => config('jdr.campaign.npc_statuses'),
            'categories' => config('jdr.campaign.npc_information_categories'),
        ]);
    }

    public function store(NpcRequest $request, NpcPortraitService $portraits): RedirectResponse
    {
        $npc = Npc::create($request->payload());

        if ($request->hasFile('portrait')) {
            $portraits->replace($npc, $request->file('portrait'));
        }

        return redirect()->route('gm.npcs.detail', $npc)->with('success', 'PNJ créé.');
    }

    public function update(NpcRequest $request, Npc $npc, NpcPortraitService $portraits): RedirectResponse
    {
        $npc->update($request->payload());

        if ($request->hasFile('portrait')) {
            $portraits->replace($npc, $request->file('portrait'));
        }

        return back()->with('success', 'PNJ mis à jour.');
    }

    public function destroy(Npc $npc, NpcPortraitService $portraits): RedirectResponse
    {
        $portraits->remove($npc);
        $npc->delete();

        return redirect()->route('gm.npcs.index')->with('success', 'PNJ supprimé.');
    }

    // ── Secrets MJ ────────────────────────────────────────────────────────

    public function storeSecret(NpcSecretRequest $request, Npc $npc): RedirectResponse
    {
        $npc->secrets()->create($request->validated());

        return back()->with('success', 'Secret ajouté.');
    }

    public function destroySecret(Npc $npc, NpcSecret $secret): RedirectResponse
    {
        $this->ensureOwnership($npc, $secret->npc_id);
        $secret->delete();

        return back()->with('success', 'Secret supprimé.');
    }

    // ── Informations révélables ───────────────────────────────────────────

    public function storeInformation(NpcInformationRequest $request, Npc $npc): RedirectResponse
    {
        $npc->informations()->create($request->validated());

        return back()->with('success', 'Information ajoutée.');
    }

    public function destroyInformation(Npc $npc, NpcInformation $information): RedirectResponse
    {
        $this->ensureOwnership($npc, $information->npc_id);
        $information->delete();

        return back()->with('success', 'Information supprimée.');
    }

    // ── Révélation ────────────────────────────────────────────────────────

    public function reveal(RevealNpcRequest $request, Npc $npc, NpcRevealService $reveal): RedirectResponse
    {
        $count = $reveal->revealNpc($npc, $request->userIds(), $request->input('relationship'));

        return back()->with('success', $count > 0
            ? "PNJ révélé à {$count} joueur(s)."
            : 'Ces joueurs connaissaient déjà ce personnage.');
    }

    public function revealInformation(
        RevealNpcRequest $request,
        Npc $npc,
        NpcInformation $information,
        NpcRevealService $reveal,
    ): RedirectResponse {
        $this->ensureOwnership($npc, $information->npc_id);
        $count = $reveal->revealInformation($information, $request->userIds());

        return back()->with('success', $count > 0
            ? "Information révélée à {$count} joueur(s)."
            : 'Ces joueurs connaissaient déjà cette information.');
    }

    // ── Export ────────────────────────────────────────────────────────────

    /** Export JSON réservé au MJ : contient les notes et les secrets. */
    public function export(Request $request, NpcImportService $service, ?Npc $npc = null): JsonResponse
    {
        $npcs = $npc instanceof Npc
            ? collect([$npc->load('house', 'secrets', 'informations')])
            : Npc::query()->with('house', 'secrets', 'informations')->orderBy('name')->get();

        $filename = $npc instanceof Npc ? 'pnj-'.$npc->id.'.json' : 'pnj-ashura.json';

        return response()
            ->json($service->export($npcs), 200, [
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Une sous-ressource doit appartenir au PNJ de l'URL : sans ce contrôle,
     * un secret pourrait être atteint via n'importe quel autre PNJ.
     */
    private function ensureOwnership(Npc $npc, int $ownerId): void
    {
        abort_unless($npc->id === $ownerId, 404);
    }
}
