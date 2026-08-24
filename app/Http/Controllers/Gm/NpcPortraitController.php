<?php

namespace App\Http\Controllers\Gm;

use App\Http\Controllers\Controller;
use App\Models\Npc;
use App\Services\NpcPortraitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NpcPortraitController extends Controller
{
    public function update(Request $request, Npc $npc, NpcPortraitService $portraits): RedirectResponse
    {
        $request->validate([
            'portrait' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ]);

        $portraits->replace($npc, $request->file('portrait'));

        return back()->with('success', 'Portrait du PNJ mis à jour.');
    }

    public function destroy(Npc $npc, NpcPortraitService $portraits): RedirectResponse
    {
        $portraits->remove($npc);

        return back()->with('success', 'Portrait du PNJ retiré.');
    }
}
