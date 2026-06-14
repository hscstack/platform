<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('app.admin_email')],
            [
                'name'               => config('app.admin_name', 'ADMIN'),
                'password'           => Hash::make(config('app.admin_password')),
                'email_verified_at'  => now(),
            ]
        );
    }
}
