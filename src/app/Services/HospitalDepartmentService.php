<?php

namespace App\Services;

use App\Repositories\HospitalDepartmentInterface;
use App\Http\Requests\RequestCreateHospitalDepartment;
use App\Http\Requests\RequestUpdateHospitalDepartment;
use App\Models\Department;
use App\Models\HospitalDepartment;
use App\Models\HospitalService;
use App\Repositories\DepartmentRepository;
use App\Repositories\HospitalServiceRepository;
use App\Repositories\InforHospitalRepository;
use Throwable;

class HospitalDepartmentService
{
    protected HospitalDepartmentInterface $hospitalDepartment;

    public function __construct(
        HospitalDepartmentInterface $hospitalDepartment
    ) {
        $this->hospitalDepartment = $hospitalDepartment;
    }

    public function add(RequestCreateHospitalDepartment $request)
    {
        try {
            $user = auth()->guard('user_api')->user();
            $department = DepartmentRepository::findById($request->id_department);
            if (empty($department)) {
                return response()->json(['message' => 'Không tìm thấy khoa !'], 404);
            }

            $data = array_merge($request->all(),[ 'id_hospital' => $user->id]);
            $hospitalDepartment = $this->hospitalDepartment->createHosDepart($data);
            return response()->json([
                'message' => 'Thêm khoa cho bệnh viện thành công ! ',
                'data' => $hospitalDepartment,
                'status' => 201,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function edit(RequestUpdateHospitalDepartment $request, $id)
    {
        try {
            $user = auth()->guard('user_api')->user();
            $hospitalDepartment = $this->hospitalDepartment->findById($id);
            if (empty($hospitalDepartment)) {
                return response()->json([
                    'message' => 'Không tìm thấy khoa này của bệnh viện !',
                    'status' => 404,
                ], 404);
            }

            if ($user->id != $hospitalDepartment->id_hospital) {
                return response()->json(['message' => 'Bạn không có quyền chỉnh sửa !'], 403);
            }

            $hospitalDepartment = $this->hospitalDepartment->updateHospitalDepartment($hospitalDepartment, $request->all()); 
            return response()->json([
                'message' => 'Cập nhật thông tin khoa cho bệnh viện thành công ! ',
                'data' => $hospitalDepartment,
                'status' => 201,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function delete($id)
    {
        try {
            $user = auth()->guard('user_api')->user();
            $hospitalDepartment = $this->hospitalDepartment->findById($id);
            if (empty($hospitalDepartment)) {
                return response()->json(['message' => 'Không tìm thấy khoa trong bệnh viện !'], 404);
            }

            if ($user->id != $hospitalDepartment->id_hospital) {
                return response()->json(['message' => 'Bạn không có quyền !'], 403);
            }

            $count = HospitalServiceRepository::getHospitalService(['id_hospital_department' => $id])->count();
            if ($count > 0) {
                return response()->json(['message' => 'Khoa này đang có dịch vụ , bạn không được xóa nó !'], 400);
            }

            $hospitalDepartment->delete();

            return response()->json([
                'message' => 'Xóa khoa của bệnh viện thành công ! ',
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function departmentOfHospital($id)
    {
        try {
            $hospital = InforHospitalRepository::getInforHospital(['id_hospital' => $id])->first();
            if(empty($hospital)) {
                return response()->json([
                    'message' => 'Không tìm thấy bệnh viện !',
                    'status' => 404,
                ], 201);
            }
            $filter = (object) [
                'id_hospital' => $id,
            ];
            $hospitalDepartments = $this->hospitalDepartment->searchHospitalDepartment($filter)->get();
            return response()->json([
                'message' => 'Xem tất cả khoa của bệnh viện thành công ! ',
                'data' => $hospitalDepartments,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function details($id)
    {
        try {

            $filter = (object) [
                'id' => $id,
            ];
            $hospitalDepartment = $this->hospitalDepartment->searchHospitalDepartment($filter)->first();
            if (empty($hospitalDepartment)) {
                return response()->json(['message' => 'Không tìm thấy khoa trong bệnh viện !'], 404);
            }
            return response()->json([
                'message' => 'Lấy tất cả khoa của bệnh viện thành công !',
                'data' => $hospitalDepartment,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
