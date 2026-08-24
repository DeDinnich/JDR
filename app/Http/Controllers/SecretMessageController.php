<?php

namespace App\Http\Controllers;

use App\Models\SecretMessage;
use App\Services\SecretMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SecretMessageController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        $messages = $request->user()->receivedMessages()
            ->whereNull('read_at')
            ->oldest()
            ->get(['id', 'body', 'priority', 'created_at'])
            ->map(fn (SecretMessage $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'priority' => $message->priority,
                'created_at' => $message->created_at,
                'delete_url' => route('messages.destroy', $message),
            ]);

        return response()->json($messages);
    }

    public function read(Request $request, SecretMessage $secretMessage, SecretMessageService $service): JsonResponse
    {
        abort_unless($secretMessage->recipient_id === $request->user()->id, 403);
        $service->markAsRead($secretMessage);

        return response()->json(['read_at' => $secretMessage->read_at?->toIso8601String()]);
    }

    public function destroy(
        Request $request,
        SecretMessage $secretMessage,
        SecretMessageService $service,
    ): JsonResponse|RedirectResponse {
        abort_unless(
            $secretMessage->sender_id === $request->user()->id
                || $secretMessage->recipient_id === $request->user()->id,
            403,
        );

        $service->delete($secretMessage);

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('success', 'Message secret supprimé des deux côtés.');
    }
}
