<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class VerificationController extends Controller
{
    /**
     * Verify the user's email address.
     *
     * @param  Request  $request
     * @param  int  $id
     * @param  string  $hash
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Check if hash matches
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Invalid verification link'
            ], 401);
        }

        // Check if user has already verified email
        if ($user->hasVerifiedEmail()) {
            // Return JSON response if API request
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Email already verified'
                ]);
            }

            // Redirect to frontend if web request
            return redirect()->to(config('app.frontend_url') . '/email-verified?already=true');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Return JSON response if API request
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Email verified successfully'
            ]);
        }

        // Redirect to frontend if web request
        return redirect()->to(config('app.frontend_url') . '/email-verified?success=true');
    }

    /**
     * Resend the email verification notification.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified'
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent'
        ]);
    }
}
