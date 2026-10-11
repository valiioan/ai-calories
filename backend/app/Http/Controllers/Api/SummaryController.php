<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Journal\SummaryReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __invoke(Request $request, SummaryReader $summary): JsonResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date', 'required_without:from'],
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date', 'required_with:from', 'after_or_equal:from'],
        ]);

        if (! empty($data['date']) && empty($data['from'])) {
            return response()->json($summary->day($request->user(), $data['date']));
        }

        return response()->json([
            'data' => $summary->range($request->user(), $data['from'], $data['to']),
        ]);
    }
}
