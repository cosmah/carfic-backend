<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterSubscriptionController extends Controller
{
    /**
     * Subscribe to the newsletter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:newsletter_subscriptions,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $subscription = NewsletterSubscription::create([
            'email' => $request->email,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Successfully subscribed to the newsletter',
            'data' => $subscription
        ], 201);
    }

    /**
     * Unsubscribe from the newsletter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:newsletter_subscriptions,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $subscription = NewsletterSubscription::where('email', $request->email)->first();

        $subscription->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully unsubscribed from the newsletter',
        ]);
    }

    /**
     * Get all subscriptions (protected by auth).
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $subscriptions = NewsletterSubscription::all();

        return response()->json([
            'data' => $subscriptions
        ]);
    }

    /**
     * Get active subscriptions (protected by auth).
     *
     * @return \Illuminate\Http\Response
     */
    public function getActive()
    {
        $subscriptions = NewsletterSubscription::where('is_active', true)->get();

        return response()->json([
            'data' => $subscriptions
        ]);
    }

    /**
     * Delete a subscription (protected by auth).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $subscription = NewsletterSubscription::findOrFail($id);
        $subscription->delete();

        return response()->json([
            'message' => 'Subscription deleted successfully'
        ]);
    }
}
