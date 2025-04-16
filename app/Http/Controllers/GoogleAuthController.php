<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless()
            // Removed setRedirectUrl as it is not a valid method
            ->redirect()
            ->getTargetUrl();

        Log::info('Google OAuth Redirect URL: ' . $redirectUrl);

        return response()->json([
            'url' => $redirectUrl,
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate([
                'google_id' => $googleUser->id,
            ], [
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'password' => Hash::make(rand(10000, 99999)),
            ]);

            $token = $user->createToken('google-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 500);
        }
    }
}
