<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
        //ADMIN
        [
            'username'=> 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('111'),
            'role' => 'admin',
            'position' => null,
            'authorized_permissions' => null,
            'status' => '1',
        ],

        [
            'username'=> 'Authorized User',
            'email'=> 'authorized@gmail.com',
            'password'=> Hash::make('111'),
            'role'=> 'authorized_user',
            'position' => 'Supreme Student Council',
            'authorized_permissions' => json_encode(['campus_events', 'hazard_points']),
            'status'=> '1',
        ],

        [
            'username'=> 'User',
            'email'=> 'user@gmail.com',
            'password'=> Hash::make('111'),
            'role'=> 'user',
            'position' => null,
            'authorized_permissions' => null,
            'status'=> '1',
        ]

        ]);
    }
}
