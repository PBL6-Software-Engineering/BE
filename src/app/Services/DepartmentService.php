<?php

namespace App\Services;

use App\Http\Requests\RequestCreateDepartment;
use App\Http\Requests\RequestUpdateDepartment;
use App\Models\Department;
use App\Repositories\DepartmentInterface;
use App\Repositories\HospitalDepartmentRepository;
use App\Repositories\InforDoctorRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Throwable;

class DepartmentService
{
    protected DepartmentInterface $departmentRepository;

    public function __construct(DepartmentInterface $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
    }

    public function saveAvatar(Request $request)
    {
        if ($request->hasFile('thumbnail')) {
            $image = $request->file('thumbnail');
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_department_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/thumbnail/departments/', $filename);

            return 'storage/image/thumbnail/departments/' . $filename;
        }
    }

    public function add(RequestCreateDepartment $request)
    {
        try {
            $department = $this->departmentRepository->createDepartment($request->all());
            $thumbnail = $this->saveAvatar($request);

            $data = ['thumbnail' => $thumbnail];
            $department = $this->departmentRepository->updateDepartment($department, $data);

            return response()->json([
                'message' => 'Thêm khoa thành công !',
                'department' => $department,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function edit(RequestUpdateDepartment $request, $id)
    {
        try {
            $department = $this->departmentRepository->findById($id);
            if ($department) {
                if ($request->hasFile('thumbnail')) {
                    if ($department->thumbnail) {
                        File::delete($department->thumbnail);
                    }
                    $thumbnail = $this->saveAvatar($request);
                    $data = array_merge($request->all(), ['thumbnail' => $thumbnail]);
                    $department = $this->departmentRepository->updateDepartment($department, $data);
                } else {
                    $department = $this->departmentRepository->updateDepartment($department, $request->all());
                }

                return response()->json([
                    'message' => 'Cập nhật thông tin khoa thành công !',
                    'department' => $department,
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy khoa !',
                ], 404);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // Không nên xóa khoa , chỉ chỉnh sửa thôi
    public function delete($id)
    {
        try {
            $department = $this->departmentRepository->findById($id);
            if ($department) {
                $doctors = InforDoctorRepository::getInforDoctor(['id_department' => $id])->count();
                $hospitalDepartment = HospitalDepartmentRepository::getHospitalDepartment(['id_department' => $id])->count();
                if ($doctors > 0) {
                    return response()->json([
                        'message' => 'Không được xóa . Đang có bác sĩ thuộc khoa này !',
                    ], 400);
                }
                if ($hospitalDepartment > 0) {
                    return response()->json([
                        'message' => 'Không được xóa . Đang có bệnh viện chứa khoa này !',
                    ], 400);
                }
                // InforDoctorRepository::updateResult($doctors, ['id_department' => null]);
                // HospitalDepartmentRepository::updateHospitalDepartment($hospitalDepartment, ['id_department' => null]);
                if ($department->thumbnail) {
                    File::delete($department->thumbnail);
                }
                $department->delete();

                return response()->json([
                    'message' => 'Xóa khoa thành công !',
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy khoa !',
                ], 404);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function all(Request $request)
    {
        try {
            if ($request->paginate == true) {
                $search = $request->search;
                $orderBy = 'id';
                $orderDirection = 'ASC';

                if ($request->sortlatest == 'true') {
                    $orderBy = 'id';
                    $orderDirection = 'DESC';
                }

                if ($request->sortname == 'true') {
                    $orderBy = 'name';
                    $orderDirection = ($request->sortlatest == 'true') ? 'DESC' : 'ASC';
                }

                $filter = (object) [
                    'search' => $search ?? '',
                    'orderBy' => $orderBy,
                    'orderDirection' => $orderDirection,
                ];
                $departments = $this->departmentRepository->searchDepartment($filter)->paginate(6);

                return response()->json([
                    'message' => 'Xem tất cả khoa thành công !',
                    'department' => $departments,
                ], 201);
            } else {
                $filter = (object) [];
                $departments = $this->departmentRepository->searchDepartment($filter)->get();

                return response()->json([
                    'message' => 'Xem tất cả khoa thành công !',
                    'department' => $departments,
                ], 201);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function details(Request $request, $id)
    {
        try {
            $department = $this->departmentRepository->findById($id);
            if ($department) {
                return response()->json([
                    'message' => 'Xem chi tiết khoa thành công !',
                    'department' => $department,
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy khoa !',
                ], 404);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
