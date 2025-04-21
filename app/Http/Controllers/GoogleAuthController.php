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
            $code = $request->input('code');
            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authorization code provided',
                ], 400);
            }

            Log::info('Received authorization code:', ['code' => $code]);

            $cacheKey = 'google_oauth_code_' . md5($code);
            if (Cache::has($cacheKey)) {
                Log::warning('Authorization code already used:', ['code' => $code]);
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization code already used',
                ], 400);
            }
            Cache::put($cacheKey, true, now()->addMinutes(10));

            $provider = Socialite::driver('google')->stateless();
            $accessTokenResponse = $provider->getAccessTokenResponse($code);

            Log::info('Access token response:', ['response' => $accessTokenResponse]);

            $googleUser = $provider->userFromToken($accessTokenResponse['access_token']);

            $user = User::updateOrCreate(
                ['google_id' => $googleUser->id],
                [
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(rand(10000, 99999)),
                    'email_verified_at' => now(), // Mark email as verified
                ]
            );

            $token = $user->createToken('google-token')->plainTextToken;

            Log::info('User authenticated successfully:', ['user_id' => $user->id]);

            $response = [
                'success' => true,
                'message' => 'Google authentication successful',
                'data' => [
                    'token' => $token,
                    'user' => $user->toArray(), // Ensure user is converted to array
                ],
            ];

            // Log the exact response being sent
            Log::info('Sending response to frontend:', $response);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Google callback error: ' . $e->getMessage());
            $errorResponse = [
                'success' => false,
                'message' => 'Authentication failed',
                'errors' => ['exception' => $e->getMessage()],
            ];
            Log::info('Sending error response to frontend:', $errorResponse);
            return response()->json($errorResponse, 400);
        }
    }
}
