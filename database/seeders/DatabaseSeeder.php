<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario para probar la API (documentado en el README)
        User::updateOrCreate(
            ['email' => 'admin@construckit.test'],
            ['name' => 'Admin', 'password' => Hash::make('password')],
        );
    }
}