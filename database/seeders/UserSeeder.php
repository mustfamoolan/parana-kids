<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Admin User
        User::create([
            'name' => 'محمد المدير',
            'email' => 'admin@paranakids.com',
            'phone' => '07736182383',
            'password' => Hash::make('mz07736182383'),
            'role' => 'admin',
        ]);
    }
}
