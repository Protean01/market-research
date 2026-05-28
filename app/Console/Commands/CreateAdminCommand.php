<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {--email= : Admin email address} {--password= : Admin password}';

    protected $description = 'Create or update an admin account';

    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Admin email');
        $password = $this->option('password') ?? $this->secret('Admin password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');
            return 1;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return 1;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'             => 'Admin',
                'password'         => Hash::make($password),
                'role'             => 'admin',
                'is_password_set'  => true,
                'is_active'        => true,
                'phone_verified_at' => now(),
            ]
        );

        if (! $user->wallet) {
            $user->wallet()->create();
        }
        if (! $user->profile) {
            $user->profile()->create(['language' => 'English']);
        }

        $this->info("Admin account ready for: {$email}");
        return 0;
    }
}
