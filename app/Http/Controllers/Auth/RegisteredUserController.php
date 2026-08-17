<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\PlayerRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, PlayerRegistrationService $service): RedirectResponse
    {
        $user = $service->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('player.character')->with('success', 'Votre aventurier a rejoint la campagne.');
    }
}
