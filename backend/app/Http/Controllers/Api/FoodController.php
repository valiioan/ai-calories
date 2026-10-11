<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FoodResource;
use App\Models\Food;
use App\Services\Food\FoodCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FoodController extends Controller
{
    public function search(Request $request, FoodCatalog $catalog): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $foods = $catalog->search($data['q'], (int) ($data['page'] ?? 1));
        } catch (RuntimeException) {
            abort(503, 'Food search is unavailable.');
        }

        return response()->json([
            'data' => FoodResource::collection($foods)->resolve(),
            'meta' => ['page' => (int) ($data['page'] ?? 1), 'per_page' => 5],
        ]);
    }

    public function show(int $food): FoodResource
    {
        $model = Food::query()->whereKey($food)->firstOrFail();
        $this->authorize('view', $model);

        return new FoodResource($model);
    }
}
