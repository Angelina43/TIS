<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function authorization($credentials)
    {
        if(!Auth::attempt($credentials)) {
            return null;
        }

        return Auth::user()->createToken('token')->plainTextToken;
    }

    public function registration($data)
    {
        $user = User::create($data);

        return $user->createToken('token')->plainTextToken;
    }

    public function logout(User $user)
    {
        $user->currentAccessToken()->delete();
    }
}
