<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|string',
        ]);
         try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return response()->json(['message' => 'Database seeded successfully', 'output' => \Illuminate\Support\Facades\Artisan::output()]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }

        Log::info('Login attempt', ['email' => $request->email, 'role' => $request->role]);

        $user = User::where('email', $request->email)->where('role', $request->role)->first();

        Log::info('User found', ['user' => $user ? $user->toArray() : null]);

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::error('Login failed - password mismatch or user not found', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['Identifiants ou rôle incorrects.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'role' => $user->role,
        ]);
    }

    public function logout(Request $request)
    {
        
        return redirect('/login');
    }
}
