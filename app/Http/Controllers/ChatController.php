<?php

namespace App\Http\Controllers;

use App\Models\AiFeedback;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Services\AI\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function show(Request $request, ?ChatThread $thread = null): View
    {
        if ($thread) {
            $this->authorize('view', $thread);
            $thread->load('messages.sources');
        }

        return view('student.chat', ['thread' => $thread]);
    }

    public function send(Request $request, AiChatService $chatService): JsonResponse
    {
        $data = $request->validate([
            'thread_id' => ['nullable', 'integer', 'exists:chat_threads,id'],
            'content' => ['required', 'string', 'max:4000'],
            'context' => ['nullable', 'string', 'in:tutor,exam'],
            'source_ids' => ['nullable', 'array', 'max:5'],
            'source_ids.*' => ['integer', 'exists:ai_knowledge_chunks,id'],
            'request_id' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);
        abort_if(($data['context'] ?? 'tutor') === 'exam', 403, 'AI không khả dụng trong bài thi.');
        $thread = ! empty($data['thread_id']) ? ChatThread::query()->findOrFail($data['thread_id']) : ChatThread::query()->create([
            'public_id' => (string) Str::ulid(), 'user_id' => $request->user()->id, 'mode' => 'tutor', 'status' => 'active',
            'title' => Str::limit($data['content'], 80), 'retention_expires_at' => now()->addYear(),
        ]);
        $this->authorize('view', $thread);
        try {
            $result = $chatService->respond($request->user(), $thread, $data['content'], $data['source_ids'] ?? [], $data['request_id'] ?? null);
        } catch (RuntimeException $exception) {
            $status = str_contains($exception->getMessage(), 'budget') ? 429 : 503;

            return response()->json(['message' => $exception->getMessage()], $status);
        }

        return response()->json(['thread_id' => $thread->id, 'user_message' => $result['user_message'], 'assistant_message' => $result['assistant_message']], 201);
    }

    public function stream(Request $request, ChatMessage $message): StreamedResponse
    {
        $this->authorize('view', $message->thread);
        $content = $message->content;

        return response()->stream(function () use ($content): void {
            foreach (str_split($content, 24) as $chunk) {
                echo 'data: '.json_encode(['delta' => $chunk], JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
            echo "data: [DONE]\n\n";
        }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache', 'X-Accel-Buffering' => 'no']);
    }

    public function feedback(Request $request, ChatMessage $message): JsonResponse
    {
        $this->authorize('view', $message->thread);
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'reason' => ['nullable', 'string', 'max:100'], 'comment' => ['nullable', 'string', 'max:1000']]);

        return response()->json(['data' => AiFeedback::query()->updateOrCreate(['chat_message_id' => $message->id, 'user_id' => $request->user()->id], $data)]);
    }

    public function command(Request $request, ChatThread $thread): JsonResponse
    {
        $this->authorize('view', $thread);
        abort_if($thread->mode === 'exam', 403, 'Scene command không khả dụng trong bài thi.');
        $data = $request->validate(['command' => ['required', 'string', 'in:play,pause,reset,seek']]);

        return response()->json(['accepted' => true, 'command' => $data['command']]);
    }
}
