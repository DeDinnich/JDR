<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = ChatMessage::query()
            ->where('sender_id', '!=', $user->getKey())
            ->whereNull('read_at')
            ->whereHas('conversation', fn ($query) => $query->where(fn ($participants) => $participants
                ->where('participant_one_id', $user->getKey())
                ->orWhere('participant_two_id', $user->getKey())))
            ->count();

        return response()->json(['count' => $count]);
    }

    public function index(Request $request, ChatService $chat): View
    {
        $conversations = $chat->listFor($request->user());
        $selected = $conversations->first();

        return $this->render($request, $chat, $conversations, $selected);
    }

    public function show(Request $request, Conversation $conversation, ChatService $chat): View
    {
        abort_unless($conversation->includes($request->user()), 403);

        return $this->render($request, $chat, $chat->listFor($request->user()), $conversation);
    }

    public function store(Request $request, Conversation $conversation, ChatService $chat): JsonResponse
    {
        abort_unless($conversation->includes($request->user()), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $message = $chat->send($conversation, $request->user(), $data['body']);

        return response()->json($this->messagePayload($message), 201);
    }

    public function read(Request $request, Conversation $conversation, ChatService $chat): JsonResponse
    {
        $chat->markRead($conversation, $request->user());

        return response()->json(['read' => true]);
    }

    private function render(Request $request, ChatService $chat, $conversations, ?Conversation $selected): View
    {
        if ($selected) {
            abort_unless($selected->includes($request->user()), 403);
            $chat->markRead($selected, $request->user());
            $conversations->firstWhere('id', $selected->id)?->setAttribute('unread_count', 0);
            $selected->loadMissing(['participantOne.character', 'participantTwo.character']);
        }

        // On récupère les 100 plus récents sans laisser l'ordre implicite de
        // la relation interférer, puis on les rend du plus ancien au plus récent.
        $messages = $selected?->messages()
            ->with('sender')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->sortBy('id')
            ->values()
            ?? collect();

        return view('chat.index', [
            'conversations' => $conversations,
            'selected' => $selected,
            'other' => $selected?->otherParticipant($request->user()),
            'messages' => $messages,
        ]);
    }

    /** @return array<string, mixed> */
    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'body' => $message->body,
            'sent_at' => $message->created_at->toIso8601String(),
            'sent_at_label' => $message->created_at->format('H:i'),
        ];
    }
}
