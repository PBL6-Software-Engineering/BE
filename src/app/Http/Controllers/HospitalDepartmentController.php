<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateHospitalDepartment;
use App\Http\Requests\RequestUpdateHospitalDepartment;
use App\Models\Department;
use App\Models\HospitalDepartment;
use App\Models\HospitalService;
use Throwable;

class HospitalDepartmentController extends Controller
{
    public function add(RequestCreateHospitalDepartment $request)
    {
        try {
            $user = auth()->guard('user_api')->user();
            $department = Department::find($request->id_department);
            if (empty($department)) {
                return response()->json(['message' => 'Không tìm thấy khoa !'], 404);
            }

            $hospitalDepartment = HospitalDepartment::create(array_merge(
                $request->all(),
                [
                    'id_hospital' => $user->id,
                ]
            ));

            return response()->json([
                'message' => 'Thêm khoa cho bệnh viện thành công ! ',
                'hospital_department' => $hospitalDepartment,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function edit(RequestUpdateHospitalDepartment $request, $id)
    {
        try {
            $user = auth()->guard('user_api')->user();
            $hospitalDepartment = HospitalDepartment::find($id);
            if (empty($hospitalDepartment)) {
                return response()->json(['message' => 'Không tìm thấy khoa này của bệnh viện !'], 404);
            }

            if ($user->id != $hospitalDepartment->id_hospital) {
                return response()->json(['message' => 'Bạn không có quyền chỉnh sửa !'], 403);
            }

            $hospitalDepartment->update($request->all());

            return response()->json([
                'message' => 'Cập nhật thông tin khoa cho bệnh viện thành công ! ',
                'hospital_department' => $hospitalDepartment,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function delete($id)
    {
        try {
            $user = auth()->guard('user_api')->user();
            $hospitalDepartment = HospitalDepartment::find($id);
            if (empty($hospitalDepartment)) {
                return response()->json(['message' => 'Không tìm thấy khoa trong bệnh viện !'], 404);
            }

            if ($user->id != $hospitalDepartment->id_hospital) {
                return response()->json(['message' => 'Bạn không có quyền !'], 403);
            }

            $count = HospitalService::where('id_hospital_department', $id)->count();
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

    public function departmentOfHospital()
    {
        try {
            $user = auth()->guard('user_api')->user();
            $hospitalDepartments = HospitalDepartment::join('departments', 'hospital_departments.id_department', '=', 'departments.id')
                ->where('hospital_departments.id_hospital', $user->id)
                ->select('hospital_departments.*', 'departments.*')
                ->get();

            return response()->json([
                'message' => 'Xem tất cả khoa của bệnh viện thành công ! ',
                'hospital_departments' => $hospitalDepartments,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function details($id)
    {
        try {
            $hospitalDepartment = HospitalDepartment::join('departments', 'hospital_departments.id_department', '=', 'departments.id')
                ->where('hospital_departments.id', $id)
                ->select('hospital_departments.*', 'departments.*')
                ->first();

            if (empty($hospitalDepartment)) {
                return response()->json(['message' => 'Không tìm thấy khoa trong bệnh viện !'], 404);
            }

            return response()->json([
                'message' => 'Lấy tất cả khoa của bệnh viện thành công !',
                'hospital_departments' => $hospitalDepartment,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
