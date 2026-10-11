<?php

namespace App\Services\Journal;

use App\Models\ActivityEntry;
use App\Models\JournalRevision;
use App\Models\Meal;
use App\Models\User;
use App\Models\WeightEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RevisionUndo
{
    public function __construct(private Journal $journal) {}

    public function undo(User $user, JournalRevision $revision): JournalRevision
    {
        if ($revision->user_id !== $user->id) {
            abort(404);
        }

        $latest = $user->revisions()
            ->where('subject_type', $revision->subject_type)
            ->where('subject_id', $revision->subject_id)
            ->whereNull('undone_at')
            ->latest('id')
            ->first();

        if (! $latest || $latest->id !== $revision->id) {
            throw new HttpException(422, 'Only the latest change can be undone.');
        }

        return DB::transaction(function () use ($user, $revision) {
            $restored = match ($revision->subject_type) {
                'meal' => $this->restoreMeal($user, $revision),
                'activity' => $this->restoreActivity($user, $revision),
                'weight' => $this->restoreWeight($user, $revision),
                default => abort(404),
            };

            $revision->forceFill(['undone_at' => CarbonImmutable::now()])->save();

            return $this->journal->revise(
                $user,
                $revision->subject_type,
                (int) ($restored['id'] ?? $revision->subject_id),
                'undo',
                $revision->after,
                $restored,
            );
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function restoreMeal(User $user, JournalRevision $revision): ?array
    {
        $current = $user->meals()->whereKey($revision->subject_id)->first();
        if ($revision->action === 'created') {
            if ($current instanceof Meal) {
                $this->journal->deleteMeal($current);
            }

            return null;
        }

        $before = $revision->before;
        if (! is_array($before)) {
            abort(422, 'This change cannot be undone.');
        }

        $meal = $current instanceof Meal ? $current : null;
        $saved = $this->journal->saveMeal($user, $before, $meal);

        return $this->journal->snapshotMeal($saved);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function restoreActivity(User $user, JournalRevision $revision): ?array
    {
        $current = $user->activities()->whereKey($revision->subject_id)->first();
        if ($revision->action === 'created') {
            if ($current instanceof ActivityEntry) {
                $this->journal->deleteActivity($current);
            }

            return null;
        }

        $before = $revision->before;
        if (! is_array($before)) {
            abort(422, 'This change cannot be undone.');
        }

        $saved = $this->journal->saveActivity($user, $before, $current instanceof ActivityEntry ? $current : null);

        return $this->journal->snapshotActivity($saved);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function restoreWeight(User $user, JournalRevision $revision): ?array
    {
        $current = $user->weights()->whereKey($revision->subject_id)->first();
        if ($revision->action === 'created') {
            if ($current instanceof WeightEntry) {
                $this->journal->deleteWeight($current);
            }

            return null;
        }

        $before = $revision->before;
        if (! is_array($before)) {
            abort(422, 'This change cannot be undone.');
        }

        $saved = $this->journal->saveWeight($user, $before, $current instanceof WeightEntry ? $current : null);

        return $this->journal->snapshotWeight($saved);
    }
}
