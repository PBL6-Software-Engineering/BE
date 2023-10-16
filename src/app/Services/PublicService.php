<?php

namespace App\Services;

use App\Http\Requests\RequestCreateCategory;
use App\Http\Requests\RequestUpdateCategory;
use App\Models\Category;
use App\Repositories\ArticleRepository;
use App\Repositories\CategoryInterface;
use App\Repositories\CategoryRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\InforDoctorRepository;
use App\Repositories\InforHospitalRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Throwable;

class PublicService
{

    public function responseOK($status = 200, $data = null, $message = '')
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
        ], $status);
    }

    public function responseError($status = 400, $message = '')
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    public function readSearch(Request $request, $name, $id)
    {
        try {
            switch ($name) {
                case 'doctor':
                    $data = [
                        'id_doctor' => $id,
                    ];
                    $doctor = InforDoctorRepository::getInforDoctor($data)->first();
                    if ($doctor) {
                        $search_number = $doctor->search_number + 1;
                        $doctor = InforDoctorRepository::updateResult($doctor, ['search_number' => $search_number]);
                        return $this->responseOK(200, $doctor, 'Tăng lượt tìm đọc cho bác sĩ thành công !');
                    } else {
                        return $this->responseError(404, 'Không tìm thấy bác sĩ !');
                    }
                    break;
                
                case 'category':
                    $category = CategoryRepository::findById($id);
                    if ($category) {
                        $search_number = $category->search_number + 1;
                        $category = CategoryRepository::updateResultCategory($category, ['search_number' => $search_number]);
                        return $this->responseOK(200, $category, 'Tăng lượt tìm đọc cho danh mục thành công !');
                    } else {
                        return $this->responseError(404, 'Không tìm thấy danh mục !');
                    }
                    break;
        
                case 'article':
                    try {
                        $filter = (object) [
                            'id' => $id,
                            'is_accept' => 1,
                            'is_show' => 1,
                        ];
                        $article = ArticleRepository::searchAll($filter)->first();
                        // dùng chính biến article để cập nhật thì không được 
                        if ($article) {
                            $_article = ArticleRepository::findById($id);
                            $search_number = $article->search_number_article + 1;
                            ArticleRepository::updateArticle($_article, ['search_number' => $search_number]);
                            $article->search_number_article = $search_number;
                            return $this->responseOK(200, $article, 'Tăng lượt tìm đọc cho bài viết thành công !');
                        } else {
                            return $this->responseError(404, 'Không tìm thấy bài viết !');
                        }
                    } catch (Throwable $e) {
                        return $this->responseError(400, $e->getMessage());
                    }
                    break;
        
                case 'hospital':
                    $data = [
                        'id_hospital' => $id,
                    ];
                    $hospital = InforHospitalRepository::getInforHospital($data)->first();
                    if ($hospital) {
                        $search_number = $hospital->search_number + 1;
                        $hospital = InforHospitalRepository::updateHospital($hospital, ['search_number' => $search_number]);
                        $hospital->infrastructure = json_decode($hospital->infrastructure);
                        $hospital->location = json_decode($hospital->location);

                        return $this->responseOK(200, $hospital, 'Tăng lượt tìm đọc cho bệnh viện thành công !');
                    } else {
                        return $this->responseError(404, 'Không tìm thấy bệnh viện !');
                    }
                    break;
        
                case 'department':
                    $department = DepartmentRepository::findById($id);
                    if ($department) {
                        $search_number = $department->search_number + 1;
                        $department = DepartmentRepository::updateDepartment($department, ['search_number' => $search_number]);
                        return $this->responseOK(200, $department, 'Tăng lượt tìm đọc cho chuyên khoa thành công !');
                    } else {
                        return $this->responseError(404, 'Không tìm thấy chuyên khoa !');
                    }
                    break;
        
                default:
                    return $this->responseError(404, 'Không tìm thấy tên đối tượng cần tăng lượt tìm đọc !');
                    break;
            }
        } catch (Throwable $e) {
            return $this->responseError(400, $e->getMessage());
        }
    }
}
