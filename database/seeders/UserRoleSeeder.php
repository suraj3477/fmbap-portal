<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'state@fmbap.gov.in'],
            ['name' => 'Assam State Officer', 'password' => Hash::make('password'), 'role' => 'state_officer']
        );

        User::updateOrCreate(
            ['email' => 'inspector@bbrd.gov.in'],
            ['name' => 'BBRD Field Inspector', 'password' => Hash::make('password'), 'role' => 'bbrd_inspector']
        );

        User::updateOrCreate(
            ['email' => 'admin@jalshakti.gov.in'],
            ['name' => 'Central Govt Admin', 'password' => Hash::make('password'), 'role' => 'central_admin']
        );
    }
}