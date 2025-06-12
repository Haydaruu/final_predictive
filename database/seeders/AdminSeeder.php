<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::Create([
            "name" => "HaydarAdmin",
            "email" => "haydarAdmin@example",
            "role" => UserRole::Admin,
            'password' => Hash::make("haydar123"), 
        ]);
    }
}
