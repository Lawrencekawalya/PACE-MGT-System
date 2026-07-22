<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\RoleName;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AccessControlSeeder::class,
            SchoolSettingSeeder::class,
        ]);

        $administrator = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
        ]);
        $administrator->roles()->attach(
            Role::query()->where('name', RoleName::Administrator->value)->value('id'),
        );
    }
}
