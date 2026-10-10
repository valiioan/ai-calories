<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Resources\SettingsResource;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request): SettingsResource
    {
        $settings = $request->user()->ensureSettings();
        $settings->wasRecentlyCreated = false;
        $this->authorize('view', $settings);

        return new SettingsResource($settings);
    }

    public function update(UpdateSettingsRequest $request): SettingsResource
    {
        $settings = $request->user()->ensureSettings();
        $this->authorize('update', $settings);
        $settings->fill($request->validated());
        $settings->save();
        $settings->wasRecentlyCreated = false;

        return new SettingsResource($settings);
    }
}
