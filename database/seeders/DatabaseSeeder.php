<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
               'id' => (string) Str::uuid(),
                'name' => 'LaraSafe',
                'email' => 'sudhirrajai@proton.me',
                'email_verified_at' => now(),
                'password' => bcrypt('12345678'),
        ]);
    }
}
