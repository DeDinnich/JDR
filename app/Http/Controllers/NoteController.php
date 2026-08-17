<?php

namespace App\Http\Controllers;

use App\Http\Requests\Player\NoteRequest;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Journal personnel, partagé par les joueurs et le maître du jeu.
 *
 * Les notes appartiennent à un compte, pas à un rôle : le même contrôleur sert
 * donc les deux espaces. `$routePrefix` indique simplement à la vue vers quel
 * groupe de routes poster — le reste est identique.
 */
class NoteController extends Controller
{
    public function index(Request $request): View
    {
        // Les personnages rencontrés ont leur propre page : le glossaire.
        $notes = $request->user()->notes()->orderByDesc('pinned')->latest('updated_at')->get();

        return view('notes.index', [
            'notes' => $notes,
            'routePrefix' => $request->user()->isGameMaster() ? 'gm.notes' : 'player.notes',
        ]);
    }

    public function store(NoteRequest $request): RedirectResponse|JsonResponse
    {
        $note = $request->user()->notes()->create($request->payload());

        return $request->wantsJson()
            ? response()->json(['id' => $note->id, 'saved_at' => $note->updated_at->format('H:i')])
            : back()->with('success', 'Note ajoutée au journal.');
    }

    /**
     * Mise à jour classique, ou enregistrement automatique en arrière-plan.
     *
     * L'éditeur appelle cette route en AJAX dès que le joueur s'arrête
     * d'écrire ; on répond alors en JSON plutôt qu'en redirection.
     */
    public function update(NoteRequest $request, Note $note): RedirectResponse|JsonResponse
    {
        $note->update($request->payload());

        return $request->wantsJson()
            ? response()->json(['saved_at' => $note->fresh()->updated_at->format('H:i')])
            : back()->with('success', 'Note mise à jour.');
    }

    public function destroy(Request $request, Note $note): RedirectResponse
    {
        abort_unless($note->user_id === $request->user()->id, 403);
        $note->delete();

        return back()->with('success', 'Note supprimée.');
    }
}
