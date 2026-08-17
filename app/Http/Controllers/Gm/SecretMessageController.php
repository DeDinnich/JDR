<?php

namespace App\Http\Controllers\Gm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gm\SendSecretMessageRequest;
use App\Models\User;
use App\Services\SecretMessageService;
use Illuminate\Http\RedirectResponse;

class SecretMessageController extends Controller
{
    public function store(SendSecretMessageRequest $request, SecretMessageService $service): RedirectResponse
    {
        $recipient = User::query()->findOrFail($request->integer('recipient_id'));
        $service->send($request->user(), $recipient, $request->validated());

        return back()->with('success', 'Message secret transmis à '.$recipient->name.'.');
    }
}
