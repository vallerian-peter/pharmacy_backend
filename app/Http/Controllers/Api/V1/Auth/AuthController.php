<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\LoginResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * POST /api/v1/auth/login
     *
     * Authenticate a staff member and return a Sanctum token
     * along with their branch context, role, and permissions.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->input('username') ?? $request->input('email');

        $authData = $this->authService->login($login, $request->input('password'));

        return (new LoginResource($authData))->response()->setStatusCode(200);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Revoke the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * GET /api/v1/auth/me
     *
     * Return the currently authenticated user's profile,
     * their active branch context, role, and permissions.
     */
    public function me(Request $request): JsonResponse
    {
        $authData = $this->authService->me($request->user());

        return (new LoginResource($authData))->response()->setStatusCode(200);
    }
}
