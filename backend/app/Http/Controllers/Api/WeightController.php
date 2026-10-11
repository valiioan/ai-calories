<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WeightResource;
use App\Models\WeightEntry;
use App\Services\Journal\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WeightController extends Controller
{
    public function __construct(private Journal $journal) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        $entries = $request->user()->weights()
            ->whereDate('recorded_on', '>=', $data['from'])
            ->whereDate('recorded_on', '<=', $data['to'])
            ->orderBy('recorded_on')
            ->get();

        return WeightResource::collection($entries);
    }

    public function store(Request $request): JsonResponse
    {
        $entry = $this->save($request, null);

        return (new WeightResource($entry))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $weight): WeightResource
    {
        return new WeightResource($this->save($request, $this->owned($request, $weight)));
    }

    public function destroy(Request $request, int $weight): JsonResponse
    {
        $entry = $this->owned($request, $weight);
        $this->authorize('delete', $entry);
        $before = $this->journal->snapshotWeight($entry);
        $this->journal->deleteWeight($entry);
        $revision = $this->journal->revise($request->user(), 'weight', $entry->id, 'deleted', $before, null);

        return response()->json(['revision_id' => $revision->id]);
    }

    private function save(Request $request, ?WeightEntry $existing): WeightEntry
    {
        if ($existing instanceof WeightEntry) {
            $this->authorize('update', $existing);
        }
        $data = $request->validate([
            'recorded_on' => ['required', 'date'],
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $match = $existing ?? $request->user()->weights()->whereDate('recorded_on', $data['recorded_on'])->first();
        $before = $match instanceof WeightEntry ? $this->journal->snapshotWeight($match) : null;
        $entry = $this->journal->saveWeight($request->user(), $data, $match instanceof WeightEntry ? $match : null);
        $this->journal->revise(
            $request->user(),
            'weight',
            $entry->id,
            $before === null ? 'created' : 'updated',
            $before,
            $this->journal->snapshotWeight($entry),
        );

        return $entry;
    }

    private function owned(Request $request, int $id): WeightEntry
    {
        $entry = $request->user()->weights()->whereKey($id)->firstOrFail();
        $this->authorize('view', $entry);

        return $entry;
    }
}
