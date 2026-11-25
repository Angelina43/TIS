<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function authorization(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ],
            [
                'email.required' => 'Email is required',
                'password.required' => 'Password is required',
            ]
        );

        if ($validate->fails()) {
            return response()->json($validate->errors(), 400);
        }

        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $token = $user->createToken('token')->plainTextToken;

            return json_encode([
                'success' => true,
                'code' => 200,
                'message' => 'Success',
                'token' => $token,
            ]);
        }

        return response()->json([
            'success' => false,
            'code' => 401,
            'message' => 'Authorization failed',
        ]);
    }

    public function registration(Request $request) {

        $validate = Validator::make($request->all(), [
            'email' => 'required|unique:users',
            'password' => 'required|min:3|regex:/^[a-zA-Z\0-9]+$/u',
            'first_name' => 'required|min:2|regex:/^[a-zA-Z]+$/u',
            'last_name' => 'required|regex:/^[a-zA-Z_-]+$/u',
        ],
            [
                'email.required' => 'Email is required',
                'email.unique' => 'Email already exists',
                'email.email' => 'Invalid email address',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 3 characters',
                'password.regex' => 'Invalid password',
                'first_name.required' => 'First name is required',
                'first_name.min' => 'First name must be at least 2 characters',
                'first_name.regex' => 'Invalid first name',
                'last_name.required' => 'Last name is required',
                'last_name.regex' => 'Invalid last name',
            ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), 400);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => $request->password,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
        ]);

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'success' => true,
            'code' => 201,
            'message' => 'Success',
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
