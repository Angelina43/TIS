<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
class AuthController extends Controller
{

    public function __construct(private AuthService $authService)
    {}

    public function authorization(AuthRequest $request) {

        $token = $this->authService->authorization($request->validated());

        if (!$token) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'Authorization failed',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Success',
            'token' => $token,
        ]);
    }
    public function registration(RegisterRequest $request) {

        $token = $this->authService->registration($request->validated());

        return response()->json([
            'success' => true,
            'code' => 201,
            'message' => 'Success',
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->noContent();
    }
}
