<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([ 'user' => $user, 'token' => $token ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([ 'user' => $user, 'token' => $token ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Logged out'], 200);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());
        return response()->json($user);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $user->password = $request->validated()['password'];
        $user->save();
        return response()->json(['message' => 'Password updated']);
    }
}
