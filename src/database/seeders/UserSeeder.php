<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * 要件で指定されたメール認証済みの一般ユーザーを登録する。
     *
     * @return void
     */
    public function run(): void
    {
        User::create([
            'name' => 'user1',
            'email' => 'user1@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'user2',
            'email' => 'user2@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
    }
}
