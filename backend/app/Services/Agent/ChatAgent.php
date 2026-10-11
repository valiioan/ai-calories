<?php

namespace App\Services\Agent;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Proposal;
use App\Models\User;
use App\Services\Food\FoodCatalog;
use App\Services\Journal\LocalDay;
use App\Services\Journal\Proposals;
use App\Services\Journal\SummaryReader;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ChatAgent
{
    public function __construct(
        private OpenAiClient $openai,
        private ToolCatalog $tools,
        private FoodCatalog $foods,
        private Proposals $proposals,
        private SummaryReader $summary,
        private LocalDay $days,
    ) {}

    /**
     * @return array{user_message: Message, assistant_message: Message, proposal: ?Proposal, actions: list<array{tool_name: string, status: string}>, retry: bool}
     */
    public function reply(User $user, Conversation $conversation, string $content): array
    {
        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $content,
        ]);

        $pending = $user->proposals()
            ->where('conversation_id', $conversation->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $control = $pending instanceof Proposal ? $this->control($content) : null;
        if ($control === 'confirm' && $pending instanceof Proposal) {
            $revision = $this->proposals->confirm($user, $pending);
            $assistant = $this->assistant($conversation, $this->savedText($user), null, $revision->id);

            return $this->result($userMessage, $assistant, $pending->refresh(), [], false);
        }
        if ($control === 'cancel' && $pending instanceof Proposal) {
            $this->proposals->cancel($user, $pending);
            $assistant = $this->assistant($conversation, $this->cancelledText($user), null, null);

            return $this->result($userMessage, $assistant, $pending->refresh(), [], false);
        }

        try {
            return $this->complete($user, $conversation, $userMessage, $content);
        } catch (RuntimeException) {
            $assistant = $this->assistant($conversation, $this->unavailableText($user), null, null);

            return $this->result($userMessage, $assistant, null, [], true);
        }
    }

    /**
     * @return array{user_message: Message, assistant_message: Message, proposal: ?Proposal, actions: list<array{tool_name: string, status: string}>, retry: bool}
     */
    private function complete(User $user, Conversation $conversation, Message $userMessage, string $content): array
    {
        $definitions = $this->tools->definitions();
        $allowed = collect($definitions)->pluck('name')->all();
        $input = [['role' => 'user', 'content' => $content]];
        $previous = null;
        $actions = [];
        $proposal = null;
        $text = '';

        for ($round = 0; $round < 6; $round++) {
            $response = $this->openai->respond($this->instructions($user), $input, $definitions, $previous);
            $previous = isset($response['id']) ? (string) $response['id'] : null;
            $calls = [];
            foreach ($response['output'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                if (($item['type'] ?? '') === 'message') {
                    $text = $this->messageText($item) ?: $text;
                }
                if (($item['type'] ?? '') === 'function_call') {
                    $calls[] = $item;
                }
            }
            if ($calls === []) {
                break;
            }

            $outputs = [];
            foreach ($calls as $call) {
                $name = (string) ($call['name'] ?? '');
                $arguments = $this->arguments($call['arguments'] ?? '{}');
                if (! in_array($name, $allowed, true)) {
                    $this->audit($user, $conversation, $name === '' ? 'unknown' : $name, $arguments, ['error' => 'Unknown tool.'], 'rejected');
                    $actions[] = ['tool_name' => $name === '' ? 'unknown' : $name, 'status' => 'rejected'];
                    $outputs[] = $this->output($call, ['error' => 'Unknown tool.']);
                    continue;
                }

                try {
                    $result = $this->execute($user, $conversation, $name, $arguments, $proposal);
                    $proposal = $result['proposal'] ?? $proposal;
                    unset($result['proposal']);
                    $this->audit($user, $conversation, $name, $arguments, $result, 'succeeded');
                    $actions[] = ['tool_name' => $name, 'status' => 'succeeded'];
                    $outputs[] = $this->output($call, $result);
                } catch (Throwable $exception) {
                    $error = ['error' => $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'The tool failed.'];
                    $this->audit($user, $conversation, $name, $arguments, $error, 'failed');
                    $actions[] = ['tool_name' => $name, 'status' => 'failed'];
                    $outputs[] = $this->output($call, $error);
                }
            }

            $input = $outputs;
            if ($previous === null) {
                break;
            }
        }

        if ($text === '') {
            $text = $proposal instanceof Proposal ? $this->previewText($user) : $this->clarifyText($user);
        }

        $assistant = $this->assistant($conversation, $text, $proposal?->id, null);
        $assistant->forceFill(['tool_calls' => $actions])->save();

        return $this->result($userMessage, $assistant, $proposal, $actions, false);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function execute(User $user, Conversation $conversation, string $name, array $arguments, ?Proposal $current): array
    {
        return match ($name) {
            'search_food' => [
                'foods' => array_map(fn ($food) => [
                    'id' => $food->id,
                    'description' => $food->description,
                    'energy_kcal_per_100g' => $food->energy_kcal === null ? null : (float) $food->energy_kcal,
                    'protein_g_per_100g' => $food->protein_g === null ? null : (float) $food->protein_g,
                    'carbohydrate_g_per_100g' => $food->carbohydrate_g === null ? null : (float) $food->carbohydrate_g,
                    'fat_g_per_100g' => $food->fat_g === null ? null : (float) $food->fat_g,
                    'fiber_g_per_100g' => $food->fiber_g === null ? null : (float) $food->fiber_g,
                    'serving' => [
                        'size' => $food->serving_size === null ? null : (float) $food->serving_size,
                        'unit' => $food->serving_unit,
                        'gram_weight' => $food->gram_weight === null ? null : (float) $food->gram_weight,
                        'household' => $food->household_serving,
                    ],
                ], $this->foods->search((string) $arguments['query'], (int) ($arguments['page'] ?? 1))),
            ],
            'preview_meal' => $this->staged($this->proposals->stageMeal($user, $conversation, $arguments)),
            'preview_update' => $this->staged($this->proposals->stageUpdate($user, $conversation, $arguments)),
            'preview_delete' => $this->staged($this->proposals->stageDelete($user, $conversation, (int) $arguments['meal_id'])),
            'preview_activity' => $this->staged($this->proposals->stageActivity($user, $conversation, $arguments)),
            'preview_weight' => $this->staged($this->proposals->stageWeight($user, $conversation, $arguments)),
            'get_report' => $this->report($user, $arguments),
            default => ['error' => 'Unknown tool.'],
        };
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function report(User $user, array $arguments): array
    {
        $date = $arguments['date'] ?? null;
        if (is_string($date) && $date !== '') {
            return ['day' => $this->summary->day($user, $date)];
        }
        $from = $arguments['from'] ?? null;
        $to = $arguments['to'] ?? null;
        if (! is_string($from) || ! is_string($to) || $from === '' || $to === '') {
            throw new InvalidArgumentException('A report needs a date or a range.');
        }

        return ['days' => $this->summary->range($user, $from, $to)];
    }

    /**
     * @return array<string, mixed>
     */
    private function staged(Proposal $proposal): array
    {
        return [
            'proposal' => $proposal,
            'proposal_id' => $proposal->id,
            'status' => 'pending',
            'kind' => $proposal->kind,
            'preview' => $proposal->payload,
        ];
    }

    private function instructions(User $user): string
    {
        $locale = $user->ensureSettings()->locale === 'en' ? 'en' : 'ro';
        $today = $this->days->today($user);
        $yesterday = $this->days->yesterday($user);
        $timezone = $this->days->timezone($user);
        $meals = $this->journalLines($user);
        $language = $locale === 'en' ? 'English' : 'Romanian';

        return <<<TEXT
You are the CalorAI journal assistant. Reply in {$language}, briefly.
Today for this user is {$today}. Yesterday is {$yesterday}. Time zone: {$timezone}. Use these dates for "today" and "yesterday".
Recent meals, with ids you must reuse for changes and deletions:
{$meals}
Call search_food before any meal preview. Search in English. Pass the returned food id to preview_meal or preview_update. Do not invent energy or macros.
Do not save, update, or delete records yourself. Preview tools only prepare a card. If the request is ambiguous, ask one short question and do not preview.
If the amount is missing, assume a common portion and say so in assumptions.
A deletion or a change must use a meal id from the list above.
TEXT;
    }

    private function journalLines(User $user): string
    {
        $yesterday = $this->days->yesterday($user);
        [$start] = $this->days->utcBounds($user, $yesterday);
        [, $end] = $this->days->utcBounds($user, $this->days->today($user));
        $meals = $user->meals()->with('items')->where('eaten_at', '>=', $start)->where('eaten_at', '<', $end)->orderBy('eaten_at')->get();
        if ($meals->isEmpty()) {
            return 'none';
        }

        return $meals->map(function ($meal) use ($user) {
            $names = $meal->items->pluck('custom_name')->filter()->implode(', ');
            $kcal = $meal->items->sum(fn ($item) => (float) $item->energy_kcal);

            return $meal->id.' '.$this->days->localDate($user, $meal->eaten_at).' '.$this->days->localTime($user, $meal->eaten_at).' '.$meal->meal_type.' '.$names.' '.$kcal.' kcal';
        })->implode("\n");
    }

    private function control(string $text): ?string
    {
        $plain = (string) Str::of($text)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]/', '')->squish();
        $confirm = ['da', 'yes', 'ok', 'confirma', 'confirm', 'salveaza', 'save', 'da salveaza', 'ok salveaza', 'yes save'];
        $cancel = ['nu', 'no', 'renunta', 'anuleaza', 'cancel'];
        if (in_array($plain, $confirm, true)) {
            return 'confirm';
        }
        if (in_array($plain, $cancel, true)) {
            return 'cancel';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function arguments(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $call
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function output(array $call, array $payload): array
    {
        return [
            'type' => 'function_call_output',
            'call_id' => (string) ($call['call_id'] ?? ''),
            'output' => json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}',
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function messageText(array $item): string
    {
        $chunks = [];
        foreach ($item['content'] ?? [] as $part) {
            if (is_array($part) && ($part['type'] ?? '') === 'output_text') {
                $chunks[] = (string) ($part['text'] ?? '');
            }
        }

        return trim(implode("\n", $chunks));
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>|null  $result
     */
    private function audit(User $user, Conversation $conversation, string $name, array $arguments, ?array $result, string $status): void
    {
        $user->agentActions()->create([
            'conversation_id' => $conversation->id,
            'tool_name' => $name,
            'arguments' => $arguments,
            'result' => $result,
            'status' => $status,
        ]);
    }

    private function assistant(Conversation $conversation, string $content, ?int $proposalId, ?int $revisionId): Message
    {
        return $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'proposal_id' => $proposalId,
            'revision_id' => $revisionId,
        ]);
    }

    /**
     * @param  list<array{tool_name: string, status: string}>  $actions
     * @return array{user_message: Message, assistant_message: Message, proposal: ?Proposal, actions: list<array{tool_name: string, status: string}>, retry: bool}
     */
    private function result(Message $userMessage, Message $assistant, ?Proposal $proposal, array $actions, bool $retry): array
    {
        return [
            'user_message' => $userMessage,
            'assistant_message' => $assistant,
            'proposal' => $proposal,
            'actions' => $actions,
            'retry' => $retry,
        ];
    }

    private function savedText(User $user): string
    {
        return $this->english($user) ? 'Saved. You can undo this change.' : 'Am salvat. Poți anula modificarea.';
    }

    private function cancelledText(User $user): string
    {
        return $this->english($user) ? 'Cancelled. Nothing was saved.' : 'Am anulat. Nu am salvat nimic.';
    }

    private function unavailableText(User $user): string
    {
        return $this->english($user) ? 'The assistant is temporarily unavailable. You can retry.' : 'Asistentul este temporar indisponibil. Poți relua.';
    }

    private function previewText(User $user): string
    {
        return $this->english($user) ? 'Here is the estimate. It is not in the journal until you confirm.' : 'Aceasta este estimarea. Nu intră în jurnal până apeși Confirmă.';
    }

    private function clarifyText(User $user): string
    {
        return $this->english($user) ? 'I need a little more detail before I can estimate that.' : 'Îmi trebuie puțin mai multe detalii ca să pot estima.';
    }

    private function english(User $user): bool
    {
        return $user->ensureSettings()->locale === 'en';
    }
}
