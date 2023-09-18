<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admins')->insert([
            'name' => 'Nguyễn Văn Mạnh',
            'email' => 'nguyenvanmanh2001it1@gmail.com',
            // 'username' => 'vanmanhit1',
            'role' => '2',
            'password' => Hash::make('nguyenvanmanh2001it1'),
            'avatar' => 'storage/image/avatars/admin.png',
            'address' => 'Phú Đa - Phú Vang - Thừa Thiên Huế',
            'date_of_birth' => '2001-08-29',
            'gender' => 1,
            'phone' => '0971404372',
        ]);
    }
}
