<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([

            // Admin
            [
                'name' => 'Jacky Hertanto',
                'email' => 'jackyh@gmail.com',
                'password' => Hash::make('jackyh'),
                'role' => 'admin',
                'profpic' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // User 1
            [
                'name' => 'John',
                'email' => 'john12@gmail.com',
                'password' => Hash::make('john'),
                'role' => 'user',
                'profpic' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // User 2
            [
                'name' => 'Bobby',
                'email' => 'bobbyhuntrix@gmail.com',
                'password' => Hash::make('bobby'),
                'role' => 'user',
                'profpic' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Maya Creator',
                'email' => 'maya@example.com',
                'password' => Hash::make('maya'),
                'role' => 'user',
                'profpic' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
