<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\Agent\ChatAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()->conversations()->latest('id')->get(['id', 'title', 'created_at', 'updated_at']);

        return response()->json(['data' => $conversations]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['title' => ['nullable', 'string', 'max:255']]);
        $conversation = $request->user()->conversations()->create(['title' => $data['title'] ?? null]);

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => [],
        ], 201);
    }

    public function show(Request $request, int $conversation): JsonResponse
    {
        $model = $this->owned($request, $conversation);
        $model->load('messages.proposal');

        return response()->json([
            'id' => $model->id,
            'title' => $model->title,
            'messages' => MessageResource::collection($model->messages),
        ]);
    }

    public function message(Request $request, int $conversation, ChatAgent $agent): JsonResponse
    {
        $model = $this->owned($request, $conversation);
        $this->authorize('update', $model);
        $data = $request->validate(['content' => ['required', 'string', 'max:4000']]);
        $result = $agent->reply($request->user(), $model, $data['content']);
        $result['assistant_message']->load('proposal');
        $result['proposal']?->refresh();

        return response()->json([
            'user_message' => new MessageResource($result['user_message']),
            'assistant_message' => new MessageResource($result['assistant_message']),
            'proposal' => $result['proposal'] ? new \App\Http\Resources\ProposalResource($result['proposal']) : null,
            'actions' => $result['actions'],
            'retry' => $result['retry'],
        ]);
    }

    private function owned(Request $request, int $id): Conversation
    {
        $conversation = $request->user()->conversations()->whereKey($id)->firstOrFail();
        $this->authorize('view', $conversation);

        return $conversation;
    }
}
