<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => config('app.admin_email')],
            [
                'name' => config('app.admin_name', 'ADMIN'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');

    }
}
