<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use App\Services\Journal\Proposals;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProposalController extends Controller
{
    public function __construct(private Proposals $proposals) {}

    public function update(Request $request, int $proposal): ProposalResource
    {
        $model = $this->owned($request, $proposal);
        $this->authorize('update', $model);
        $data = $request->validate([
            'eaten_on' => ['sometimes', 'date'],
            'time' => ['sometimes', 'date_format:H:i'],
            'meal_type' => ['sometimes', 'in:breakfast,lunch,dinner,snack'],
            'assumptions' => ['nullable', 'string', 'max:2000'],
            'uncertainty' => ['nullable', 'string', 'max:2000'],
            'original_text' => ['nullable', 'string', 'max:4000'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.food_id' => ['nullable', 'integer'],
            'items.*.custom_name' => ['nullable', 'string', 'max:255'],
            'items.*.display_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit' => ['required_with:items', 'in:g,ml,serving,piece'],
            'items.*.energy_kcal' => ['nullable', 'numeric'],
            'items.*.protein_g' => ['nullable', 'numeric'],
            'items.*.carbohydrate_g' => ['nullable', 'numeric'],
            'items.*.fat_g' => ['nullable', 'numeric'],
            'items.*.fiber_g' => ['nullable', 'numeric'],
            'items.*.nutrition_source' => ['nullable', 'in:usda,manual'],
            'performed_on' => ['sometimes', 'date'],
            'name' => ['sometimes', 'string', 'max:255'],
            'duration_minutes' => ['sometimes', 'integer', 'between:1,1440'],
            'intensity' => ['sometimes', 'in:low,moderate,high'],
            'energy_kcal' => ['sometimes', 'numeric', 'between:0,5000'],
            'note' => ['nullable', 'string', 'max:255'],
            'recorded_on' => ['sometimes', 'date'],
            'weight_kg' => ['sometimes', 'numeric', 'between:30,300'],
        ]);

        if (isset($data['items'])) {
            $data['items'] = array_map(function (array $item) {
                if (($item['unit'] ?? null) === 'piece') {
                    $item['unit'] = 'serving';
                }

                return $item;
            }, $data['items']);
        }

        try {
            return new ProposalResource($this->proposals->revise($request->user(), $model, $data));
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }
    }

    public function confirm(Request $request, int $proposal): JsonResponse
    {
        $model = $this->owned($request, $proposal);
        $this->authorize('update', $model);
        $revision = $this->proposals->confirm($request->user(), $model);

        return response()->json([
            'proposal' => new ProposalResource($model->refresh()),
            'revision_id' => $revision->id,
        ]);
    }

    public function cancel(Request $request, int $proposal): ProposalResource
    {
        $model = $this->owned($request, $proposal);
        $this->authorize('update', $model);

        return new ProposalResource($this->proposals->cancel($request->user(), $model));
    }

    private function owned(Request $request, int $id): Proposal
    {
        $proposal = $request->user()->proposals()->whereKey($id)->firstOrFail();
        $this->authorize('view', $proposal);

        return $proposal;
    }
}
