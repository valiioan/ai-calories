<?php

namespace App\Services\Journal;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class SummaryReader
{
    public function __construct(private LocalDay $days) {}

    /**
     * @return array<string, mixed>
     */
    public function day(User $user, string $date): array
    {
        [$start, $end] = $this->days->utcBounds($user, $date);
        $meals = $user->meals()->with('items')->where('eaten_at', '>=', $start)->where('eaten_at', '<', $end)->get();
        $activities = $user->activities()->where('performed_at', '>=', $start)->where('performed_at', '<', $end)->get();
        $weight = $user->weights()->whereDate('recorded_on', $date)->first();
        $profile = $user->ensureProfile();

        $intake = 0.0;
        $protein = 0.0;
        $carbs = 0.0;
        $fat = 0.0;
        foreach ($meals as $meal) {
            foreach ($meal->items as $item) {
                $intake += (float) $item->energy_kcal;
                $protein += (float) $item->protein_g;
                $carbs += (float) $item->carbohydrate_g;
                $fat += (float) $item->fat_g;
            }
        }
        $out = (float) $activities->sum(fn ($activity) => (float) $activity->energy_kcal);

        return [
            'date' => $date,
            'calorie_target' => $profile->calorie_target,
            'energy_in_kcal' => round($intake, 2),
            'energy_out_kcal' => round($out, 2),
            'net_kcal' => round($intake - $out, 2),
            'weight_kg' => $weight ? (float) $weight->weight_kg : null,
            'protein_g' => round($protein, 2),
            'carbohydrate_g' => round($carbs, 2),
            'fat_g' => round($fat, 2),
            'logged' => $meals->isNotEmpty(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function range(User $user, string $from, string $to): array
    {
        if ($from > $to) {
            abort(422, 'The date range is invalid.');
        }

        $period = CarbonPeriod::create($from, $to);
        if ($period->count() > 366) {
            abort(422, 'The date range is too long.');
        }

        $days = [];
        foreach ($period as $date) {
            $days[] = $this->day($user, $date->toDateString());
        }

        return $days;
    }

    public function latestWeight(User $user): ?float
    {
        $entry = $user->weights()->orderByDesc('recorded_on')->first();

        return $entry ? (float) $entry->weight_kg : null;
    }

    public function clock(User $user): CarbonImmutable
    {
        return CarbonImmutable::now($this->days->timezone($user));
    }
}
