<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phone = '+260972829811';

        $admin = User::updateOrCreate(
            ['phone_number' => $phone],
            [
                'name' => 'System Admin',
                'email' => 'admin@mr.local',
                'password' => bcrypt('password'), // Required by schema but login is via OTP
                'is_admin' => true,
                'role' => 'admin',
                'phone_verified_at' => now(),
            ]
        );

        // Ensure admin has a wallet and profile
        if (! $admin->wallet) {
            $admin->wallet()->create(['balance' => 0, 'points' => 0]);
        }

        if (! $admin->profile) {
            $admin->profile()->create([
                'gender' => 'Other',
                'birth_year' => 1990,
                'location' => 'Lusaka',
                'is_complete' => true,
            ]);
        }

        // Ensure attributes are correct if user already existed
        $admin->update([
            'is_admin' => true,
            'role' => 'admin',
        ]);
    }
}
