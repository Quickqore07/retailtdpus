<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role with permissions from Permission schema
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['permissions' => json_encode(Permission::schema())]
        );

        // Create admin user (using DB to avoid re-hashing the already hashed password)
        $existingUser = User::where('email', 'quickqore7@gmail.com')->first();

        if (!$existingUser) {
            DB::table('users')->insert([
                'name' => 'admin',
                'username' => 'admin',
                'email' => 'quickqore7@gmail.com',
                'role_id' => $role->id,
                'registration_flag' => 1,
                'active' => 1,
                'password' => Hash::make('password'),  // Password is: password
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

