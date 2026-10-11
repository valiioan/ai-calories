<?php

namespace App\Services\Journal;

use App\Models\ActivityEntry;
use App\Models\Conversation;
use App\Models\Food;
use App\Models\JournalRevision;
use App\Models\Meal;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WeightEntry;
use App\Services\Food\FoodCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Proposals
{
    public function __construct(
        private FoodCatalog $foods,
        private NutrientSnapshot $snapshots,
        private Journal $journal,
        private LocalDay $days,
    ) {}

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function stageMeal(User $user, ?Conversation $conversation, array $arguments): Proposal
    {
        return $this->store($user, $conversation, 'meal_create', $this->mealPayload($user, $arguments), null);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function stageUpdate(User $user, ?Conversation $conversation, array $arguments): Proposal
    {
        $meal = $this->meal($user, (int) ($arguments['meal_id'] ?? 0));
        $current = $this->journal->snapshotMeal($meal);
        $payload = $this->mealPayload($user, array_merge($current, $arguments, [
            'original_text' => $arguments['original_text'] ?? $current['original_text'],
        ]));
        $payload['meal_id'] = $meal->id;

        return $this->store($user, $conversation, 'meal_update', $payload, $meal->id);
    }

    public function stageDelete(User $user, ?Conversation $conversation, int $mealId): Proposal
    {
        $meal = $this->meal($user, $mealId);
        $payload = $this->journal->snapshotMeal($meal);
        $payload['label'] = collect($payload['items'])->pluck('custom_name')->filter()->implode(', ');

        return $this->store($user, $conversation, 'meal_delete', $payload, $meal->id);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function stageActivity(User $user, ?Conversation $conversation, array $arguments): Proposal
    {
        $payload = [
            'performed_on' => (string) ($arguments['performed_on'] ?? $this->days->today($user)),
            'time' => (string) ($arguments['time'] ?? $this->days->nowTime($user)),
            'name' => trim((string) ($arguments['name'] ?? '')),
            'duration_minutes' => isset($arguments['duration_minutes']) ? (int) $arguments['duration_minutes'] : null,
            'intensity' => $arguments['intensity'] ?? 'moderate',
            'energy_kcal' => round((float) ($arguments['energy_kcal'] ?? 0), 2),
            'note' => $arguments['note'] ?? null,
            'source' => 'estimated',
        ];
        if ($payload['name'] === '' || $payload['energy_kcal'] < 0) {
            throw new InvalidArgumentException('The activity is incomplete.');
        }

        return $this->store($user, $conversation, 'activity', $payload, null);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function stageWeight(User $user, ?Conversation $conversation, array $arguments): Proposal
    {
        $payload = [
            'recorded_on' => (string) ($arguments['recorded_on'] ?? $this->days->today($user)),
            'weight_kg' => round((float) ($arguments['weight_kg'] ?? 0), 2),
            'note' => $arguments['note'] ?? null,
        ];
        if ($payload['weight_kg'] < 30 || $payload['weight_kg'] > 300) {
            throw new InvalidArgumentException('The weight is outside the allowed range.');
        }

        return $this->store($user, $conversation, 'weight', $payload, null);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function revise(User $user, Proposal $proposal, array $changes): Proposal
    {
        $this->ownedPending($user, $proposal);
        $payload = $proposal->payload;
        if (in_array($proposal->kind, ['meal_create', 'meal_update'], true)) {
            $payload = $this->mealPayload($user, array_merge($payload, $changes));
            if ($proposal->kind === 'meal_update') {
                $payload['meal_id'] = $proposal->target_id;
            }
            $payload['corrected'] = true;
        } elseif ($proposal->kind === 'activity') {
            $payload = array_merge($payload, array_intersect_key($changes, array_flip([
                'performed_on', 'time', 'name', 'duration_minutes', 'intensity', 'energy_kcal', 'note',
            ])));
        } elseif ($proposal->kind === 'weight') {
            $payload = array_merge($payload, array_intersect_key($changes, array_flip(['recorded_on', 'weight_kg', 'note'])));
        }

        $proposal->forceFill(['payload' => $payload])->save();

        return $proposal->refresh();
    }

    public function confirm(User $user, Proposal $proposal): JournalRevision
    {
        $this->ownedPending($user, $proposal);

        return DB::transaction(function () use ($user, $proposal) {
            $revision = match ($proposal->kind) {
                'meal_create' => $this->commitMeal($user, $proposal, null, 'created'),
                'meal_update' => $this->commitMeal($user, $proposal, $this->meal($user, (int) $proposal->target_id), 'updated'),
                'meal_delete' => $this->commitMealDelete($user, $proposal),
                'activity' => $this->commitActivity($user, $proposal),
                'weight' => $this->commitWeight($user, $proposal),
                default => abort(422, 'This proposal cannot be confirmed.'),
            };
            $proposal->forceFill(['status' => 'confirmed'])->save();

            return $revision;
        });
    }

    public function cancel(User $user, Proposal $proposal): Proposal
    {
        $this->ownedPending($user, $proposal);
        $proposal->forceFill(['status' => 'cancelled'])->save();

        return $proposal;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function mealPayload(User $user, array $arguments): array
    {
        $items = $arguments['items'] ?? null;
        if (! is_array($items) || $items === []) {
            throw new InvalidArgumentException('A meal needs at least one food.');
        }

        $built = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('A meal item is invalid.');
            }
            $built[] = $this->item($item);
        }

        $mealType = (string) ($arguments['meal_type'] ?? '');
        if (! in_array($mealType, ['breakfast', 'lunch', 'dinner', 'snack'], true)) {
            throw new InvalidArgumentException('The meal type is invalid.');
        }

        return [
            'eaten_on' => (string) ($arguments['eaten_on'] ?? $this->days->today($user)),
            'time' => (string) ($arguments['time'] ?? $this->days->nowTime($user)),
            'meal_type' => $mealType,
            'note' => $arguments['note'] ?? null,
            'original_text' => $arguments['original_text'] ?? null,
            'assumptions' => $arguments['assumptions'] ?? null,
            'uncertainty' => $arguments['uncertainty'] ?? null,
            'corrected' => (bool) ($arguments['corrected'] ?? false),
            'items' => $built,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function item(array $item): array
    {
        if (! empty($item['food_id']) && ($item['nutrition_source'] ?? 'usda') !== 'manual') {
            $food = $this->foods->findOrFetch((int) $item['food_id']);
            if (! $food instanceof Food) {
                throw new InvalidArgumentException('The food was not found.');
            }

            return $this->snapshots->fromFood(
                $food,
                (float) $item['quantity'],
                (string) $item['unit'],
                isset($item['display_name']) ? (string) $item['display_name'] : ($item['custom_name'] ?? null),
            );
        }

        if (($item['nutrition_source'] ?? null) === 'usda' && isset($item['energy_kcal'])) {
            return $item;
        }

        return $this->snapshots->manual($item);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function store(User $user, ?Conversation $conversation, string $kind, array $payload, ?int $targetId): Proposal
    {
        return $user->proposals()->create([
            'conversation_id' => $conversation?->id,
            'kind' => $kind,
            'payload' => $payload,
            'status' => 'pending',
            'target_id' => $targetId,
        ]);
    }

    private function commitMeal(User $user, Proposal $proposal, ?Meal $existing, string $action): JournalRevision
    {
        $before = $existing instanceof Meal ? $this->journal->snapshotMeal($existing) : null;
        $saved = $this->journal->saveMeal($user, $proposal->payload, $existing);
        $after = $this->journal->snapshotMeal($saved);

        return $this->journal->revise($user, 'meal', $saved->id, $action, $before, $after);
    }

    private function commitMealDelete(User $user, Proposal $proposal): JournalRevision
    {
        $meal = $this->meal($user, (int) $proposal->target_id);
        $before = $this->journal->snapshotMeal($meal);
        $this->journal->deleteMeal($meal);

        return $this->journal->revise($user, 'meal', $meal->id, 'deleted', $before, null);
    }

    private function commitActivity(User $user, Proposal $proposal): JournalRevision
    {
        $saved = $this->journal->saveActivity($user, $proposal->payload);

        return $this->journal->revise($user, 'activity', $saved->id, 'created', null, $this->journal->snapshotActivity($saved));
    }

    private function commitWeight(User $user, Proposal $proposal): JournalRevision
    {
        $existing = $user->weights()->whereDate('recorded_on', $proposal->payload['recorded_on'])->first();
        $before = $existing instanceof WeightEntry ? $this->journal->snapshotWeight($existing) : null;
        $saved = $this->journal->saveWeight($user, $proposal->payload, $existing instanceof WeightEntry ? $existing : null);
        $action = $before === null ? 'created' : 'updated';

        return $this->journal->revise($user, 'weight', $saved->id, $action, $before, $this->journal->snapshotWeight($saved));
    }

    private function meal(User $user, int $id): Meal
    {
        $meal = $user->meals()->whereKey($id)->first();
        if (! $meal instanceof Meal) {
            abort(404);
        }

        return $meal;
    }

    private function ownedPending(User $user, Proposal $proposal): void
    {
        if ($proposal->user_id !== $user->id) {
            abort(404);
        }
        if ($proposal->status !== 'pending') {
            abort(422, 'This proposal is no longer open.');
        }
    }
}
