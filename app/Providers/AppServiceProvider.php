<?php

namespace App\Providers;

use App\Services\MigrationSyncService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($event->command !== 'migrate' || MigrationSyncService::$skipAutoSync) {
                return;
            }

            if (! Schema::hasTable('migrations')) {
                if (! MigrationSyncService::databaseHasExistingTables()) {
                    return;
                }

                \Illuminate\Support\Facades\Artisan::call('migrate:install');
            }

            MigrationSyncService::sync();
        });

        VerifyEmail::createUrlUsing(function ($notifiable) {
            $params = [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ];
            return url('/api/email/verify/' . $params['id'] . '/' . $params['hash']);
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });

        // Extend Socialite with a custom HTTP client, but disable debug output
        Socialite::extend('google', function ($app) {
            $config = $app['config']['services.google'];

            $guzzleClient = new Client([
                'debug' => false, // Disable debug output
            ]);

            return Socialite::buildProvider(
                \Laravel\Socialite\Two\GoogleProvider::class,
                $config
            )->setHttpClient($guzzleClient);
        });
    }
}
