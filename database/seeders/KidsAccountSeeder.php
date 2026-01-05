<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class KidsAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create a parent account
        $parent = User::where('email', 'parent@example.com')->first();

        if (!$parent) {
            $parent = User::create([
                'name' => 'Parent Account',
                'email' => 'parent@example.com',
                'password' => Hash::make('password'),
                'avatar_color' => User::generateAvatarColor(),
                'role' => User::ROLE_PARENT,
                'email_verified_at' => now(),
            ]);
        }

        // Create kids accounts
        $kids = [
            [
                'name' => 'Emma',
                'email' => 'emma@example.com',
                'avatar_color' => '#FF6B6B', // Red
            ],
            [
                'name' => 'Luca',
                'email' => 'luca@example.com',
                'avatar_color' => '#4CAF50', // Green
            ],
            [
                'name' => 'Mia',
                'email' => 'mia@example.com',
                'avatar_color' => '#2196F3', // Blue
            ],
        ];

        foreach ($kids as $kidData) {
            $existingKid = User::where('email', $kidData['email'])->first();

            if (!$existingKid) {
                User::create([
                    'name' => $kidData['name'],
                    'email' => $kidData['email'],
                    'password' => Hash::make('password'), // Simple password for demo
                    'avatar_color' => $kidData['avatar_color'],
                    'role' => User::ROLE_KID,
                    'parent_id' => $parent->id,
                    'email_verified_at' => now(),
                ]);

                $this->command->info("Created kid account: {$kidData['name']} ({$kidData['email']})");
            } else {
                $this->command->info("Kid account already exists: {$kidData['name']}");
            }
        }

        $this->command->info('Kids accounts seeded successfully!');
        $this->command->info('Parent login: parent@example.com / password');
        $this->command->info('Kids login: emma@example.com / password');
        $this->command->info('Kids login: luca@example.com / password');
        $this->command->info('Kids login: mia@example.com / password');
    }
}
