<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
    * Run the database seeds.
    */
    public function run(): void
    {
        $password = config('admin.password');
        
        if (!$password) {
            throw new \RuntimeException('ADMIN_PASSWORD missing');
        }
        
        User::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make($password),
                ]
            );
        }
    }
    

    
    
    
    //! for just an account which is the admin also. 