<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        $profile = $request->user()->ensureProfile();
        $profile->wasRecentlyCreated = false;
        $this->authorize('view', $profile);

        return new ProfileResource($profile);
    }

    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $profile = $request->user()->ensureProfile();
        $this->authorize('update', $profile);
        $profile->fill($request->validated());
        $profile->save();
        $profile->wasRecentlyCreated = false;

        return new ProfileResource($profile);
    }
}
