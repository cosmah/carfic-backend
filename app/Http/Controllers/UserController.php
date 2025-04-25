<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NewsletterSubscription;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Fetch all users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Fetch a single user by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    /**
     * Fetch all active newsletter subscription emails.
     *
     * @return JsonResponse
     */
    public function fetchNewsletterEmails(): JsonResponse
    {
        $emails = NewsletterSubscription::where('is_active', true)
            ->pluck('email'); // Fetch only the email column

        return response()->json($emails);
    }
}
