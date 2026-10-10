<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request): UserResource
    {
        $user = $request->user();
        $user->wasRecentlyCreated = false;

        return new UserResource($user);
    }

    public function update(UpdateAccountRequest $request): UserResource
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return new UserResource($user);
    }
}
