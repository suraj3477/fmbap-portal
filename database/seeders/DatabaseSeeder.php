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

        $users = [
            [
                'name'        => 'Super Admin',
                'email'       => 'admin@brahmaputraboard.gov.in',
                'role'        => 'super_admin',
                'state'       => null,
                'password'    => $defaultPassword,
                'is_approved' => true,
            ],
            [
                'name'        => 'Assam Water Resources',
                'email'       => 'assam@gov.in',
                'role'        => 'state_official',
                'state'       => 'Assam',
                'password'    => $defaultPassword,
                'is_approved' => true,
            ],
            [
                'name'        => 'Brahmaputra Board',
                'email'       => 'brahmaputraboard@gov.in',
                'role'        => 'board_official',
                'state'       => null,
                'password'    => $defaultPassword,
                'is_approved' => true,
            ],
            [
                'name'        => 'Ministry of Jal Shakti',
                'email'       => 'mojs@gov.in',
                'role'        => 'mojs_official',
                'state'       => null,
                'password'    => $defaultPassword,
                'is_approved' => true,
            ],
            [
                'name'        => 'Meghalaya Water Resources',
                'email'       => 'meghalaya@gmail.com',
                'role'        => 'state_official',
                'state'       => 'Meghalaya',
                'password'    => $defaultPassword,
                'is_approved' => true,
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }

        $this->call([
            SchemeSeeder::class,
        ]);
    }
}