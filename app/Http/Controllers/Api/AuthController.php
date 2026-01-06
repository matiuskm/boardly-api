<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // validate credentials, authenticate (session), return user payload
        $credentials = $request->only('email', 'password');
        if (! Auth::attempt($credentials)) {
            return ApiResponse::error('Invalid credentials.', 'unauthorized', 401);
        }

        $request->session()->regenerate();
        return ApiResponse::success(['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        // logout and invalidate session
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        // return authenticated user payload
        return ApiResponse::success(['user' => $request->user()]);
    }
}
