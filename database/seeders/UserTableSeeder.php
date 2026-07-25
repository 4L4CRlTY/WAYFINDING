<?php

namespace Database\Seeders;

use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            'status' => '1',
        ],

        [
            'username'=> 'staff',
            'email'=> 'staff@gmail.com',
            'password'=> Hash::make('111'),
            'role'=> 'staff',
            'status'=> '1',
        ],

        [
            'username'=> 'User',
            'email'=> 'user@gmail.com',
            'password'=> Hash::make('111'),
            'role'=> 'user',
            'status'=> '1',
        ]

        ]);
    }
}
