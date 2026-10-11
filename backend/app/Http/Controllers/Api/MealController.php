<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MealWriteRequest;
use App\Http\Resources\MealResource;
use App\Models\Meal;
use App\Services\Journal\Journal;
use App\Services\Journal\LocalDay;
use App\Services\Journal\MealInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;

class MealController extends Controller
{
    public function __construct(
        private Journal $journal,
        private MealInput $input,
        private LocalDay $days,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['date' => ['required', 'date']]);
        [$start, $end] = $this->days->utcBounds($request->user(), $data['date']);
        $meals = $request->user()->meals()->with('items')
            ->where('eaten_at', '>=', $start)
            ->where('eaten_at', '<', $end)
            ->orderBy('eaten_at')
            ->get();

        return MealResource::collection($meals);
    }

    public function store(MealWriteRequest $request): JsonResponse
    {
        $meal = $this->save($request, null, 'created');

        return (new MealResource($meal))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $meal): MealResource
    {
        return new MealResource($this->owned($request, $meal));
    }

    public function update(MealWriteRequest $request, int $meal): MealResource
    {
        return new MealResource($this->save($request, $this->owned($request, $meal), 'updated'));
    }

    public function destroy(Request $request, int $meal): JsonResponse
    {
        $model = $this->owned($request, $meal);
        $this->authorize('delete', $model);
        $before = $this->journal->snapshotMeal($model);
        $this->journal->deleteMeal($model);
        $revision = $this->journal->revise($request->user(), 'meal', $model->id, 'deleted', $before, null);

        return response()->json(['revision_id' => $revision->id]);
    }

    private function save(MealWriteRequest $request, ?Meal $existing, string $action): Meal
    {
        if ($existing instanceof Meal) {
            $this->authorize('update', $existing);
        }

        try {
            $payload = $this->input->prepare($request->validated());
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        $before = $existing instanceof Meal ? $this->journal->snapshotMeal($existing) : null;
        $meal = $this->journal->saveMeal($request->user(), $payload, $existing);
        $this->journal->revise($request->user(), 'meal', $meal->id, $action, $before, $this->journal->snapshotMeal($meal));

        return $meal;
    }

    private function owned(Request $request, int $id): Meal
    {
        $meal = $request->user()->meals()->with('items')->whereKey($id)->firstOrFail();
        $this->authorize('view', $meal);

        return $meal;
    }
}
