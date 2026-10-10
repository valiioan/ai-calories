<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'first_name' => $request->string('first_name')->toString(),
                'last_name' => $request->string('last_name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
            ]);
            $user->ensureProfile();
            $user->ensureSettings();

            return $user;
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        return (new UserResource($user))->response()->setStatusCode(201);
    }
}
