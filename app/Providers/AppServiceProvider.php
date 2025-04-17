<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Client;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Customize verification URLs
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $params = [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ];

            // API verification URL
            return url('/api/email/verify/' . $params['id'] . '/' . $params['hash']);
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });


        // Extend Socialite to use a custom HTTP client with debugging enabled
        Socialite::extend('google', function ($app) {
            $config = $app['config']['services.google'];

            // Create a Guzzle client with debug enabled
            $guzzleClient = new Client([
                'debug' => true, // Enable verbose logging
            ]);

            return Socialite::buildProvider(
                \Laravel\Socialite\Two\GoogleProvider::class,
                $config
            )->setHttpClient($guzzleClient);
        });

    }
}
