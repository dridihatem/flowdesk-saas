<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Client;
use App\Models\Provider;
use App\Models\User;
use App\Services\ChatThreadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatThreadService $chatThreads,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $threads = $this->threadsFor($user);

        if ($user->hasRole('client') && $threads->isEmpty()) {
            $client = $user->clientProfile;
            abort_if(! $client, 403);
            $this->chatThreads->clientThreadFor($client);
            $threads = $this->threadsFor($user);
        }

        if ($user->hasRole('business_provider') && $threads->isEmpty()) {
            $provider = $user->providerProfile;
            abort_if(! $provider, 403);
            ChatThread::query()->firstOrCreate(
                [
                    'company_id' => $user->company_id,
                    'type' => ChatThread::TYPE_PROVIDER,
                    'subject_id' => $provider->id,
                ],
            );
            $threads = $this->threadsFor($user);
        }

        $company = $user->company;

        return view('chat.index', compact('threads', 'company'));
    }

    public function show(ChatThread $thread): View
    {
        $this->authorizeThread($thread);
        $thread->load(['company', 'messages' => fn ($q) => $q->with('user')->latest('id')->limit(200)]);
        $messages = $thread->messages->sortBy('id')->values();
        $company = $thread->company ?? auth()->user()?->company;

        return view('chat.show', compact('thread', 'messages', 'company'));
    }

    public function storeMessage(Request $request, ChatThread $thread): JsonResponse|RedirectResponse
    {
        $this->authorizeThread($thread);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = ChatMessage::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        $thread->touch();
        $message->load('user');

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'user_name' => $message->user->name,
                    'user_id' => $message->user_id,
                    'created_at' => $message->created_at->toIso8601String(),
                ],
            ]);
        }

        return back()->with('status', __('Message sent.'));
    }

    public function widgetBootstrap(Request $request): JsonResponse
    {
        $user = $request->user();
        $threads = $this->threadsForWidget($user);

        return response()->json([
            'self_id' => (string) $user->id,
            'threads' => $threads->map(fn (ChatThread $t) => [
                'id' => $t->id,
                'label' => $t->resolveDisplayNameFor($user),
                'type' => $t->type,
            ])->values(),
            'full_page_url' => route('chat.index'),
        ]);
    }

    public function messagesFull(Request $request, ChatThread $thread): JsonResponse
    {
        $this->authorizeThread($thread);
        $limit = min(200, max(1, (int) $request->query('limit', 100)));
        $msgs = $thread->messages()
            ->with('user')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values();

        return response()->json([
            'messages' => $msgs->map(fn (ChatMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'user_name' => $m->user->name,
                'user_id' => $m->user_id,
                'created_at' => $m->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function messagesJson(Request $request, ChatThread $thread): JsonResponse
    {
        $this->authorizeThread($thread);
        $after = (int) $request->query('after', 0);
        $msgs = $thread->messages()
            ->with('user')
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(100)
            ->get();

        return response()->json([
            'messages' => $msgs->map(fn (ChatMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'user_name' => $m->user->name,
                'user_id' => $m->user_id,
                'created_at' => $m->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function openClient(Request $request, Client $client): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->hasAnyRole(['company_admin', 'team_member']), 403);
        abort_if((string) $client->company_id !== (string) $user->company_id, 403);

        $thread = $this->chatThreads->clientThreadFor($client);

        return redirect()->route('chat.show', $thread);
    }

    public function openProvider(Request $request, Provider $provider): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->hasAnyRole(['company_admin', 'team_member']), 403);
        abort_if((string) $provider->company_id !== (string) $user->company_id, 403);

        $thread = ChatThread::query()->firstOrCreate(
            [
                'company_id' => $user->company_id,
                'type' => ChatThread::TYPE_PROVIDER,
                'subject_id' => $provider->id,
            ],
        );

        return redirect()->route('chat.show', $thread);
    }

    /**
     * @return Collection<int, ChatThread>
     */
    private function threadsFor(User $user)
    {
        if ($user->hasRole('client')) {
            $client = $user->clientProfile;
            abort_if(! $client, 403);

            return ChatThread::query()
                ->with('company')
                ->where('company_id', $user->company_id)
                ->where(function ($query) use ($user, $client) {
                    $query->where(function ($owned) use ($client) {
                        $owned->where('type', ChatThread::TYPE_CLIENT)
                            ->where('subject_id', $client->id);
                    })->orWhereHas('participants', fn ($participants) => $participants->where('users.id', $user->id));
                })
                ->latest('updated_at')
                ->get();
        }

        if ($user->hasRole('business_provider')) {
            $provider = $user->providerProfile;
            abort_if(! $provider, 403);

            return ChatThread::query()
                ->where('company_id', $user->company_id)
                ->where('type', ChatThread::TYPE_PROVIDER)
                ->where('subject_id', $provider->id)
                ->latest('updated_at')
                ->get();
        }

        abort_if(! $user->hasAnyRole(['company_admin', 'team_member']), 403);

        return ChatThread::query()
            ->where('company_id', $user->company_id)
            ->latest('updated_at')
            ->limit(100)
            ->get();
    }

    /**
     * @return Collection<int, ChatThread>
     */
    private function threadsForWidget(User $user): Collection
    {
        if ($user->hasRole('client')) {
            $client = $user->clientProfile;
            if (! $client) {
                return collect();
            }
            $this->chatThreads->clientThreadFor($client);

            return ChatThread::query()
                ->where('company_id', $user->company_id)
                ->where(function ($query) use ($user, $client) {
                    $query->where(function ($owned) use ($client) {
                        $owned->where('type', ChatThread::TYPE_CLIENT)
                            ->where('subject_id', $client->id);
                    })->orWhereHas('participants', fn ($participants) => $participants->where('users.id', $user->id));
                })
                ->latest('updated_at')
                ->get();
        }

        if ($user->hasRole('business_provider')) {
            $provider = $user->providerProfile;
            if (! $provider) {
                return collect();
            }
            ChatThread::query()->firstOrCreate(
                [
                    'company_id' => $user->company_id,
                    'type' => ChatThread::TYPE_PROVIDER,
                    'subject_id' => $provider->id,
                ],
            );

            return ChatThread::query()
                ->where('company_id', $user->company_id)
                ->where('type', ChatThread::TYPE_PROVIDER)
                ->where('subject_id', $provider->id)
                ->latest('updated_at')
                ->get();
        }

        if (! $user->hasAnyRole(['company_admin', 'team_member'])) {
            return collect();
        }

        return ChatThread::query()
            ->where('company_id', $user->company_id)
            ->latest('updated_at')
            ->limit(100)
            ->get();
    }

    private function authorizeThread(ChatThread $thread): void
    {
        $user = auth()->user();
        abort_if(! $user, 403);
        abort_if(! $this->chatThreads->userCanAccess($thread, $user), 403);
    }
}
