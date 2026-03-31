<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private const BASE_TOKEN_ABILITIES = ['media:read', 'playlists:write'];

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_UPLOADER,
        ]);

        $token = $user->createToken('api', $this->tokenAbilitiesFor($user))->plainTextToken;

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function registerConsumer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_CONSUMER,
        ]);

        $token = $user->createToken('consumer-api', $this->tokenAbilitiesFor($user))->plainTextToken;

        return response()->json([
            'message' => 'Consumer user successfully registered',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api', $this->tokenAbilitiesFor($user))->plainTextToken;

        return response()->json([
            'message' => 'User successfully logged in',
            'role' => $user->role,
            'token' => $token,
        ]);
    }

    public function loginConsumer(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || $user->role !== User::ROLE_CONSUMER) {
            return response()->json(['error' => 'Invalid consumer credentials'], 401);
        }

        $token = $user->createToken('consumer-api', $this->tokenAbilitiesFor($user))->plainTextToken;

        return response()->json([
            'message' => 'Consumer user successfully logged in',
            'role' => $user->role,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'User successfully logged out',
        ]);
    }

    private function tokenAbilitiesFor(User $user): array
    {
        if ($user->canUploadMedia()) {
            return [...self::BASE_TOKEN_ABILITIES, 'media:write'];
        }

        return self::BASE_TOKEN_ABILITIES;
    }
}
