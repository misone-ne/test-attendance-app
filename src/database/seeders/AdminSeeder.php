<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * 要件で指定された管理者のダミーデータを登録する。
     *
     * @return void
     */
    public function run(): void
    {
        Admin::create([
            'name' => '管理者',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
