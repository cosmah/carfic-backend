<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        Log::info('Google OAuth Redirect URL: ' . $redirectUrl);

        return response()->json([
            'success' => true,
            'message' => 'Google OAuth redirect URL generated successfully',
            'data' => [
                'url' => $redirectUrl,
            ],
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $code = $request->query('code');
            if (!$code) {
                throw new \Exception('No authorization code provided');
            }

            Log::info('Received authorization code:', ['code' => $code]);

            // Check if the code has already been processed
            $cacheKey = 'google_oauth_code_' . md5($code);
            if (Cache::has($cacheKey)) {
                Log::warning('Authorization code already used:', ['code' => $code]);
                throw new \Exception('Authorization code already used');
            }

            // Mark the code as used
            Cache::put($cacheKey, true, now()->addMinutes(10)); // Store for 10 minutes

            Log::info('Socialite redirect URI for token exchange:', [
                'redirect_uri' => config('services.google.redirect'),
            ]);

            $provider = Socialite::driver('google')->stateless();
            $accessTokenResponse = $provider->getAccessTokenResponse($code);

            Log::info('Access token response:', ['response' => $accessTokenResponse]);

            $googleUser = $provider->userFromToken($accessTokenResponse['access_token']);

            $user = User::updateOrCreate([
                'google_id' => $googleUser->id,
            ], [
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'password' => Hash::make(rand(10000, 99999)),
            ]);

            $token = $user->createToken('google-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Google authentication successful',
                'data' => [
                    'token' => $token,
                    'user' => $user,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Google callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'errors' => ['exception' => $e->getMessage()],
            ], 500);
        }
    }
}
