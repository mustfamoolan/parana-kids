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
            'name' => 'أحمد المدير',
            'email' => 'admin@paranakids.com',
            'phone' => '0501234567',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create Supplier User
        User::create([
            'name' => 'محمد المجهز',
            'email' => 'supplier@paranakids.com',
            'phone' => '0507654321',
            'code' => 'SUP001',
            'password' => Hash::make('password'),
            'role' => 'supplier',
        ]);

        // Create Delegate Users
        User::create([
            'name' => 'سارة المندوبة',
            'phone' => '0509876543',
            'code' => 'DEL001',
            'password' => Hash::make('password'),
            'role' => 'delegate',
        ]);

        User::create([
            'name' => 'علي المندوب',
            'phone' => '0504567890',
            'code' => 'DEL002',
            'password' => Hash::make('password'),
            'role' => 'delegate',
        ]);

        User::create([
            'name' => 'فاطمة المندوبة',
            'phone' => '0503210987',
            'code' => 'DEL003',
            'password' => Hash::make('password'),
            'role' => 'delegate',
        ]);
    }
}
