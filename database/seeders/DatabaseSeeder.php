<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'Ryaondido27@gmail.com',
        ], [
            'full_name' => 'Administrator',
            'password' => Hash::make('ryan@123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        User::query()->updateOrCreate([
            'email' => 'Mariah22@gmail.com',
        ], [
            'full_name' => 'Maintenance Admin',
            'password' => Hash::make('dagsnitoy'),
            'role' => 'maintenance_admin',
            'status' => 'active',
        ]);
    }
}
