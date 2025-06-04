<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully. Please check your email to verify your account.',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Log in a user.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email not verified. Please check your email for verification link.',
                'verification_required' => true
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Log out a user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Send OTP for password reset.
     */
    public function sendResetOtp(Request $request): JsonResponse
    {
        Log::info('Password reset OTP requested', ['email' => $request->email]);

        $request->validate(['email' => 'required|email|exists:users,email']);

        $otp = rand(100000, 999999);
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['otp' => $otp, 'created_at' => now()]
        );

        Log::info('OTP generated and saved', ['email' => $request->email, 'otp' => $otp]);

        // Send OTP via a templated email
        Mail::to($request->email)->send(new PasswordResetOtpMail($otp));

        Log::info('OTP email sent', ['email' => $request->email]);

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    /**
     * Verify OTP for password reset.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        Log::info('OTP verification attempt', ['email' => $request->email, 'otp' => $request->otp]);

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string'
        ]);

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record) {
            Log::warning('Invalid OTP attempt', ['email' => $request->email, 'otp' => $request->otp]);
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        $expiresAt = Carbon::parse($record->created_at)->addMinutes(5);
        if (now()->greaterThan($expiresAt)) {
            Log::warning('Expired OTP attempt', ['email' => $request->email, 'otp' => $request->otp]);
            return response()->json(['message' => 'OTP expired.'], 400);
        }

        Log::info('OTP verified', ['email' => $request->email]);
        return response()->json(['message' => 'OTP verified.']);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        Log::info('Password reset attempt', ['email' => $request->email]);

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record) {
            Log::warning('Invalid OTP on password reset', ['email' => $request->email, 'otp' => $request->otp]);
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        $expiresAt = Carbon::parse($record->created_at)->addMinutes(5);
        if (now()->greaterThan($expiresAt)) {
            Log::warning('Expired OTP on password reset', ['email' => $request->email, 'otp' => $request->otp]);
            return response()->json(['message' => 'OTP expired.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        Log::info('Password reset successful', ['email' => $request->email]);

        return response()->json(['message' => 'Password reset successful.']);
    }
}
