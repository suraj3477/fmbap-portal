<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password@123');

        // Super Admin (Brahmaputra Board acts as super admin internally)
        User::create([
            'name'       => 'Super Admin',
            'email'      => 'admin@brahmaputraboard.gov.in',
            'role'       => 'super_admin',
            'state'      => null,
            'password'   => $defaultPassword,
            'is_approved'=> true,
        ]);
    }
}