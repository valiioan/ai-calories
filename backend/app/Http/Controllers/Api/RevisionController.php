<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Journal\RevisionUndo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function undo(Request $request, int $revision, RevisionUndo $undo): JsonResponse
    {
        $model = $request->user()->revisions()->whereKey($revision)->firstOrFail();
        $this->authorize('update', $model);
        $created = $undo->undo($request->user(), $model);

        return response()->json(['revision_id' => $created->id]);
    }
}
