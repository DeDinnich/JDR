<?php

namespace App\Http\Controllers;

use App\Models\SecretMessage;
use App\Services\SecretMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecretMessageController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        return response()->json($request->user()->receivedMessages()
            ->whereNull('read_at')
            ->oldest()
            ->get(['id', 'body', 'priority', 'created_at']));
    }

    public function read(Request $request, SecretMessage $secretMessage, SecretMessageService $service): JsonResponse
    {
        abort_unless($secretMessage->recipient_id === $request->user()->id, 403);
        $service->markAsRead($secretMessage);

        return response()->json(['read_at' => $secretMessage->read_at?->toIso8601String()]);
    }
}
