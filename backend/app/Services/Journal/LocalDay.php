<?php

namespace App\Services\Journal;

use App\Models\User;
use Carbon\CarbonImmutable;

class LocalDay
{
    public function timezone(User $user): string
    {
        $timezone = $user->ensureProfile()->timezone;

        return is_string($timezone) && $timezone !== '' ? $timezone : 'Europe/Bucharest';
    }

    public function today(User $user): string
    {
        return CarbonImmutable::now($this->timezone($user))->toDateString();
    }

    public function yesterday(User $user): string
    {
        return CarbonImmutable::now($this->timezone($user))->subDay()->toDateString();
    }

    public function nowTime(User $user): string
    {
        return CarbonImmutable::now($this->timezone($user))->format('H:i');
    }

    public function combine(User $user, string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date.' '.$time, $this->timezone($user))->utc();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function utcBounds(User $user, string $date): array
    {
        $start = CarbonImmutable::parse($date, $this->timezone($user))->startOfDay()->utc();

        return [$start, $start->addDay()];
    }

    public function localDate(User $user, mixed $utc): string
    {
        return CarbonImmutable::parse($utc)->timezone($this->timezone($user))->toDateString();
    }

    public function localTime(User $user, mixed $utc): string
    {
        return CarbonImmutable::parse($utc)->timezone($this->timezone($user))->format('H:i');
    }
}
