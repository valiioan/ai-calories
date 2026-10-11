<?php

namespace App\Services\Journal;

use App\Models\ActivityEntry;
use App\Models\JournalRevision;
use App\Models\Meal;
use App\Models\MealItem;
use App\Models\User;
use App\Models\WeightEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class Journal
{
    public function __construct(private LocalDay $days) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveMeal(User $user, array $payload, ?Meal $existing = null): Meal
    {
        return DB::transaction(function () use ($user, $payload, $existing) {
            $eatenAt = $this->days->combine(
                $user,
                (string) $payload['eaten_on'],
                (string) ($payload['time'] ?? '12:00'),
            );

            $meal = $existing ?? new Meal(['user_id' => $user->id]);
            $meal->fill([
                'user_id' => $user->id,
                'eaten_at' => $eatenAt,
                'meal_type' => $payload['meal_type'],
                'note' => $payload['note'] ?? null,
                'original_text' => $payload['original_text'] ?? null,
                'assumptions' => $payload['assumptions'] ?? null,
            ]);
            $meal->save();
            $meal->items()->delete();
            foreach ($payload['items'] as $item) {
                $meal->items()->create($item);
            }

            return $meal->load('items');
        });
    }

    public function deleteMeal(Meal $meal): void
    {
        $meal->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveActivity(User $user, array $payload, ?ActivityEntry $existing = null): ActivityEntry
    {
        $entry = $existing ?? new ActivityEntry(['user_id' => $user->id]);
        $entry->fill([
            'user_id' => $user->id,
            'performed_at' => $this->days->combine($user, (string) $payload['performed_on'], (string) ($payload['time'] ?? '12:00')),
            'name' => $payload['name'],
            'duration_minutes' => $payload['duration_minutes'] ?? null,
            'intensity' => $payload['intensity'] ?? null,
            'energy_kcal' => $payload['energy_kcal'],
            'note' => $payload['note'] ?? null,
            'source' => $payload['source'] ?? 'manual',
        ]);
        $entry->save();

        return $entry;
    }

    public function deleteActivity(ActivityEntry $entry): void
    {
        $entry->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveWeight(User $user, array $payload, ?WeightEntry $existing = null): WeightEntry
    {
        $recordedOn = (string) $payload['recorded_on'];
        $entry = $existing ?? $user->weights()->whereDate('recorded_on', $recordedOn)->first() ?? new WeightEntry(['user_id' => $user->id]);
        $entry->fill([
            'user_id' => $user->id,
            'recorded_on' => $recordedOn,
            'weight_kg' => $payload['weight_kg'],
            'note' => $payload['note'] ?? null,
        ]);
        $entry->save();

        return $entry;
    }

    public function deleteWeight(WeightEntry $entry): void
    {
        $entry->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotMeal(Meal $meal): array
    {
        $meal->loadMissing('items');
        $user = $meal->user ?? User::query()->findOrFail($meal->user_id);

        return [
            'id' => $meal->id,
            'eaten_on' => $this->days->localDate($user, $meal->eaten_at),
            'time' => $this->days->localTime($user, $meal->eaten_at),
            'meal_type' => $meal->meal_type,
            'note' => $meal->note,
            'original_text' => $meal->original_text,
            'assumptions' => $meal->assumptions,
            'items' => $meal->items->map(fn (MealItem $item) => [
                'food_id' => $item->food_id,
                'custom_name' => $item->custom_name,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'grams' => $item->grams === null ? null : (float) $item->grams,
                'energy_kcal' => $item->energy_kcal === null ? null : (float) $item->energy_kcal,
                'protein_g' => $item->protein_g === null ? null : (float) $item->protein_g,
                'carbohydrate_g' => $item->carbohydrate_g === null ? null : (float) $item->carbohydrate_g,
                'fat_g' => $item->fat_g === null ? null : (float) $item->fat_g,
                'fiber_g' => $item->fiber_g === null ? null : (float) $item->fiber_g,
                'nutrition_source' => $item->nutrition_source,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotActivity(ActivityEntry $entry): array
    {
        $user = $entry->user ?? User::query()->findOrFail($entry->user_id);

        return [
            'id' => $entry->id,
            'performed_on' => $this->days->localDate($user, $entry->performed_at),
            'time' => $this->days->localTime($user, $entry->performed_at),
            'name' => $entry->name,
            'duration_minutes' => $entry->duration_minutes,
            'intensity' => $entry->intensity,
            'energy_kcal' => (float) $entry->energy_kcal,
            'note' => $entry->note,
            'source' => $entry->source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotWeight(WeightEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'recorded_on' => $entry->recorded_on instanceof CarbonImmutable
                ? $entry->recorded_on->toDateString()
                : CarbonImmutable::parse($entry->recorded_on)->toDateString(),
            'weight_kg' => (float) $entry->weight_kg,
            'note' => $entry->note,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function revise(User $user, string $subjectType, int $subjectId, string $action, ?array $before, ?array $after): JournalRevision
    {
        return $user->revisions()->create([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
