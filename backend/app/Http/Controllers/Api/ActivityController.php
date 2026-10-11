<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\ActivityEntry;
use App\Services\Journal\Journal;
use App\Services\Journal\LocalDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function __construct(private Journal $journal, private LocalDay $days) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        [$start] = $this->days->utcBounds($request->user(), $data['from']);
        [, $end] = $this->days->utcBounds($request->user(), $data['to']);
        $entries = $request->user()->activities()
            ->where('performed_at', '>=', $start)
            ->where('performed_at', '<', $end)
            ->orderBy('performed_at')
            ->get();

        return ActivityResource::collection($entries);
    }

    public function store(Request $request): JsonResponse
    {
        $entry = $this->save($request, null, 'created');

        return (new ActivityResource($entry))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $activity): ActivityResource
    {
        return new ActivityResource($this->save($request, $this->owned($request, $activity), 'updated'));
    }

    public function destroy(Request $request, int $activity): JsonResponse
    {
        $entry = $this->owned($request, $activity);
        $this->authorize('delete', $entry);
        $before = $this->journal->snapshotActivity($entry);
        $this->journal->deleteActivity($entry);
        $revision = $this->journal->revise($request->user(), 'activity', $entry->id, 'deleted', $before, null);

        return response()->json(['revision_id' => $revision->id]);
    }

    private function save(Request $request, ?ActivityEntry $existing, string $action): ActivityEntry
    {
        if ($existing instanceof ActivityEntry) {
            $this->authorize('update', $existing);
        }
        $data = $request->validate([
            'performed_on' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'between:1,1440'],
            'intensity' => ['nullable', Rule::in(['low', 'moderate', 'high'])],
            'energy_kcal' => ['required', 'numeric', 'between:0,5000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $data['source'] = 'manual';
        $before = $existing instanceof ActivityEntry ? $this->journal->snapshotActivity($existing) : null;
        $entry = $this->journal->saveActivity($request->user(), $data, $existing);
        $this->journal->revise($request->user(), 'activity', $entry->id, $action, $before, $this->journal->snapshotActivity($entry));

        return $entry;
    }

    private function owned(Request $request, int $id): ActivityEntry
    {
        $entry = $request->user()->activities()->whereKey($id)->firstOrFail();
        $this->authorize('view', $entry);

        return $entry;
    }
}
