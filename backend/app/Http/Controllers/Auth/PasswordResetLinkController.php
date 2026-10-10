<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Notifications\ResetPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        ResetPassword::$latestRelativeUrl = null;
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages([
                'email' => ['Așteaptă un minut înainte să ceri din nou resetarea.'],
            ]);
        }

        $payload = [
            'message' => 'Dacă există un cont pentru această adresă, am trimis instrucțiunile de resetare.',
        ];
        $url = ResetPassword::$latestRelativeUrl;
        ResetPassword::$latestRelativeUrl = null;

        if (is_string($url) && $url !== '') {
            $payload['reset_url'] = $url;
        }

        return response()->json($payload);
    }
}
