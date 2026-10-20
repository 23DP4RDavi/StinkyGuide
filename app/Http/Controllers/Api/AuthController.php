<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInterest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user ? $user->only('id', 'name', 'email', 'bio') : null,
            'interests' => $user ? $user->interests()->pluck('interest')->all() : [],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:24', 'regex:/^[\pL\pN_ -]+$/u'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user->only('id', 'name', 'email', 'bio'), 'interests' => []], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        if (! Auth::attempt(['email' => strtolower($credentials['email']), 'password' => $credentials['password']], true)) {
            throw ValidationException::withMessages(['email' => ['That email and password do not match. Try again.']]);
        }

        $request->session()->regenerate();
        $user = $request->user();

        return response()->json([
            'user' => $user->only('id', 'name', 'email', 'bio'),
            'interests' => $user->interests()->pluck('interest')->all(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:24', 'regex:/^[\pL\pN_ -]+$/u'],
            'bio' => ['nullable', 'string', 'max:280'],
        ]);
        $user = $request->user();
        $user->update([
            'name' => trim($validated['name']),
            'bio' => trim($validated['bio'] ?? ''),
        ]);

        return response()->json(['user' => $user->only('id', 'name', 'email', 'bio')]);
    }
}
