<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Exception;

class AdminSeeder extends Seeder
{
    /**
     * Seed the default admin user.
     */
    public function run(): void
    {
        try {
            // Check if admin already exists to prevent duplicates
            if (!User::where('email', 'carfic@admin.com')->exists()) {
                User::create([
                    'name' => 'Admin',
                    'email' => 'carfic@admin.com',
                    'password' => Hash::make('admin123'),
                    'user_type' => 'admin',
                    'email_verified_at' => now(),
                ]);
                
                $this->command->info('Admin user seeded successfully.');
            } else {
                $this->command->info('Admin user already exists. Skipping...');
            }
        } catch (Exception $e) {
            $this->command->error('Failed to seed admin user: ' . $e->getMessage());
        }
    }
}
