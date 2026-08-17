<?php

namespace App\Http\Controllers\Gm;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SecretMessage;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $players = User::query()
            ->where('role', UserRole::Player->value)
            ->with(['character.currentMap', 'character.inventoryItems', 'character.states'])
            ->orderBy('name')
            ->get();
        $messages = SecretMessage::query()->with('recipient')->latest()->limit(12)->get();

        return view('gm.dashboard', compact('players', 'messages'));
    }
}
