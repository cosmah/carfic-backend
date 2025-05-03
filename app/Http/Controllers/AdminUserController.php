<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Create a new user with specified user type and send verification email.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response|JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'user_type' => ['required', 'string', 'in:admin,client'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
            'user_type' => $request->user_type ?? 'client', // Default to client if not provided
        ]);

        // Trigger verification email
        event(new Registered($user));

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    /**
     * Delete a user account.
     *
     * @param int $id
     * @return Response|JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function destroy(Request $request, $id): Response|JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        }

        // Prevent deleting the last admin
        if ($user->user_type === 'admin' && User::where('user_type', 'admin')->count() === 1) {
            return response()->json(['message' => 'Cannot delete the last admin user'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
