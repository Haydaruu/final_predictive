<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::Create([
            "name" => "Haydar SuperAdmin",
            "email" => "haydarSuperAdmin@example",
            "role" => UserRole::SuperAdmin,
            'password' => Hash::make("haydar123"), 
        ]);
    }
}
