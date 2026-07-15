<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@exaple.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'user',
        //     'phoneno' => '000-000-0000',
        //     'emp_id' => '000-000-0000',
        //     'status' => 'active',
        // ]);
        $this->call([
            // LabourRoleSeeder::class,
            // LabourSeeder::class,
            // MaterialSeeder::class,
            // SiteDetailSeeder::class,
            // DailyLabourEntrySeeder::class,
            // DailyMaterialEntrySeeder::class,
            // RoomListSeeder::class,
            // RoomWorkListSeeder::class,
            // WorkkittyListSeeder::class,
            // WorkKittyAssignmentSeeder::class,
        ]);
    }
}

