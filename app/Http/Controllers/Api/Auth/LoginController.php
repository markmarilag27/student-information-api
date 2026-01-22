<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginFormRequest;
use App\Http\Resources\UserResource;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginFormRequest $request)
    {
        $user = $request->findUser();

        $accessToken = $user->createToken($request->userAgent() ?? 'api-token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'access_token' => $accessToken,
        ]);
    }
}
