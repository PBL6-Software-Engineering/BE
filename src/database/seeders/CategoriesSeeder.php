<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Factories\FakeImageFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriesSeeder extends Seeder
{
    // public function run()
    // {
    //     $categories = [
    //         'Sức khỏe răng miệng',
    //         'Dược liệu',
    //         'Tâm lý - Tâm thần',
    //         'Thể dục thể thao',
    //         'Lão hóa lành mạnh',
    //         'Thói quen lành mạnh',
    //         'Ăn uống lành mạnh',
    //         'Ung thư - Ung bướu',
    //         'Bệnh về não & hệ thần kinh',
    //         'Bệnh truyền nhiễm',
    //         'Bệnh tiêu hóa',
    //         'Bệnh về máu',
    //         'Sức khỏe tình dục',
    //         'Da liễu',
    //         'Dị ứng',
    //         'Chăm sóc giấc ngủ',
    //         'Bệnh tai mũi họng',
    //         'Bệnh cơ xương khớp',
    //         'Bệnh thận và Đường tiết niệu',
    //         'Bệnh hô hấp',
    //         'Bệnh tim mạch',
    //         'Tiểu đường',
    //         'Sức khỏe mắt',
    //         'Thuốc và thực phẩm chức năng',
    //         'Mang thai',
    //         'Sức khỏe phụ nữ',
    //         'Sức khỏe',
    //         'Sức khỏe nam giới',
    //         'Nuôi dạy con',
    //     ];

    //     foreach ($categories as $category) {
    //         DB::table('categories')->insert([
    //             'name' => $category,
    //             'thumbnail' => 'storage/image/thumbnail/category.png',
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);
    //     }
    // }

    public function run()
    {
        $pathFolder = 'public/storage/image/thumbnail/categories';
        if (!File::exists($pathFolder)) {
            File::makeDirectory($pathFolder, 0755, true);
        }

        $categoryNames = [
            'Sức khỏe răng miệng',
            'Dược liệu',
            'Tâm lý - Tâm thần',
            'Thể dục thể thao',
            'Lão hóa lành mạnh',
            'Thói quen lành mạnh',
            'Ăn uống lành mạnh',
            'Ung thư - Ung bướu',
            'Bệnh về não & hệ thần kinh',
            'Bệnh truyền nhiễm',
            'Bệnh tiêu hóa',
            'Bệnh về máu',
            'Sức khỏe tình dục',
            'Da liễu',
            'Dị ứng',
            'Chăm sóc giấc ngủ',
            'Bệnh tai mũi họng',
            'Bệnh cơ xương khớp',
            'Bệnh thận và Đường tiết niệu',
            'Bệnh hô hấp',
            'Bệnh tim mạch',
            'Tiểu đường',
            'Sức khỏe mắt',
            'Thuốc và thực phẩm chức năng',
            'Mang thai',
            'Sức khỏe phụ nữ',
            'Sức khỏe',
            'Sức khỏe nam giới',
        ];

        $thumbnails = [];

        foreach ($categoryNames as $index => $categoryName) {
            $thumbnail = FakeImageFactory::new()->createThumbnailCategory();
            while (!$thumbnail) {
                $thumbnail = FakeImageFactory::new()->createThumbnailCategory();
            }
            $thumbnails[$index] = 'storage/image/thumbnail/categories/' . $thumbnail;
        }
        
        foreach ($categoryNames as $index => $categoryName) {
            Category::create([
                'name' => $categoryName,
                'thumbnail' => $thumbnails[$index],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

