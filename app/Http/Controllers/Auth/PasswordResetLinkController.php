<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request, PasswordResetService $service): RedirectResponse
    {
        $service->sendLink($request->string('email')->toString());

        // Réponse volontairement identique pour une adresse connue ou inconnue.
        return back()->with(
            'status',
            'Si cette adresse appartient à un aventurier, un lien vient de lui être envoyé.',
        );
    }
}
