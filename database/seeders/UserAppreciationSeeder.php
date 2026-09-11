<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAppreciation;
use Illuminate\Database\Seeder;

class UserAppreciationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 5) {
            $users = User::factory()->count(10)->create();
        }

        foreach ($users as $appreciator) {
            $sampleCount = min(rand(2, 4), max(1, $users->count() - 1));
            $targets = $users->where('id', '!=', $appreciator->id)->random($sampleCount);

            foreach ($targets as $target) {
                UserAppreciation::firstOrCreate([
                    'user_id' => $target->id,
                    'appreciator_id' => $appreciator->id,
                ]);
            }
        }
    }
}
