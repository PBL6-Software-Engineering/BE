<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory ;
use Illuminate\Support\Facades\Storage;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();
        $fakeImageUrl = $faker->imageUrl(200, 200, 'admins');
        $imageContent = file_get_contents($fakeImageUrl);
        $imageName = 'avatar_admin_' . time() . '.jpg';
        Storage::put('public/image/avatars/admins/' . $imageName, $imageContent);

        DB::table('admins')->insert([
            'email' => 'nguyenvanmanh2001it1@gmail.com',
            'password' => Hash::make('nguyenvanmanh2001it1'),
            'name' => 'Nguyễn Văn Mạnh',
            'date_of_birth' => '2001-08-29',
            'address' => 'Phú Đa - Phú Vang - Thừa Thiên Huế',
            'phone' => '0971404372',
            'gender' => 1,
            'avatar' => 'storage/image/avatars/admins/' . $imageName,
            'role' => 'manager',
        ]);
    }
}
