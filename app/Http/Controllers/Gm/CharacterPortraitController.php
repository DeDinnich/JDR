<?php

namespace App\Http\Controllers\Gm;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Services\CharacterPortraitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CharacterPortraitController extends Controller
{
    public function update(Request $request, Character $character, CharacterPortraitService $portraits): RedirectResponse
    {
        $data = $request->validate([
            'portrait' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ]);

        $portraits->replace($character, $data['portrait']);

        return back()->with('success', 'Portrait du personnage mis à jour.');
    }

    public function destroy(Character $character, CharacterPortraitService $portraits): RedirectResponse
    {
        $portraits->remove($character);

        return back()->with('success', 'Portrait du personnage retiré.');
    }
}
