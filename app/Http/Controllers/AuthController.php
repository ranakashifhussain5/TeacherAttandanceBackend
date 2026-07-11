<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:cr,hod',
            'program_id' => 'required_if:role,cr|nullable|exists:programs,id',
            'batch_id' => [
                'required_if:role,cr',
                'nullable',
                Rule::exists('batches', 'id')->where('program_id', $request->program_id),
            ],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'program_id' => $request->program_id,
            'batch_id' => $request->batch_id,
        ]);

        return response()->json(['message' => 'User registered successfully']);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = User::find(Auth::id());

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);

    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = User::find($request->user()->id);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_pic' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = User::find($request->user()->id);

        if ($user->profile_pic) {
            Storage::disk('public')->delete($user->profile_pic);
        }

        $path = $request->file('profile_pic')->store('profile_pics', 'public');

        $user->update(['profile_pic' => $path]);

        return response()->json(['message' => 'Profile picture updated', 'user' => $user]);
    }

     // Logout
    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $user->currentAccessToken();

        $token->delete();

        return response()->json(['message'=>'Logged out']);
    }
}
