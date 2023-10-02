<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateDepartment;
use App\Http\Requests\RequestUpdateDepartment;
use App\Models\Department;
use App\Models\HospitalDepartment;
use App\Models\InforDoctor;
use App\Models\InforHospital;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;

class DepartmentController extends Controller
{
    public function saveAvatar(Request $request){
        if ($request->hasFile('thumbnail')) {
            $image = $request->file('thumbnail');
            $filename =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_department_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/thumbnail/departments/', $filename);
            return 'storage/image/thumbnail/departments/' . $filename;
        }
    }

    public function add(RequestCreateDepartment $request){
        $department = Department::create(array_merge(
            $request->all()
        ));
        $thumbnail = $this->saveAvatar($request);
        $department->update(['thumbnail' => $thumbnail]);
        return response()->json([
            'message' => 'Thêm khoa thành công !',
            'department' => $department
        ], 201);
    }

    public function edit(RequestUpdateDepartment $request,$id){
        $department = Department::find($id);
        if($request->hasFile('thumbnail')) {
            if ($department->thumbnail) {
                File::delete($department->thumbnail);
            }
            $thumbnail = $this->saveAvatar($request);
            $department->update(array_merge($request->all(),['thumbnail' => $thumbnail]));
        } else {
            $department->update($request->all());
        }
        return response()->json([
            'message' => 'Cập nhật thông tin khoa thành công !',
            'department' => $department
        ], 201);
    }

    public function delete($id)
    {
        try {
            $department =  Department::find($id);
            if ($department) {
                InforDoctor::where("id_department",$id)->update(['id_department'=>null]); 
                HospitalDepartment::where("id_department",$id)->update(['id_department'=>null]); 
                $department->delete();
                return response()->json([
                    'message' => 'Xóa khoa thành công !',
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Không tìm thấy khoa !',
                ], 404);
            }
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Xóa khoa thấy bại ! ',
            ], 400);
        }
    }

    public function all(Request $request)
    {
        if ($request->paginate == true) { // lấy cho department 
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
        
            $departments = Department::orderBy($orderBy, $orderDirection)
                ->where('name', 'LIKE', '%' . $search . '%')
                ->orWhere('description', 'LIKE', '%' . $search . '%')
                ->paginate(6);
        
            return response()->json([
                'message' => 'Xem tất cả khoa thành công !',
                'department' => $departments,
            ], 201);
        }
        else { // lấy cho product 
            $departments = Department::all();
            return response()->json([
                'message' => 'Xem tất cả khoa thành công !',
                'department' => $departments,
            ], 201);
        }
    }

    public function details(Request $request, $id){
        $department = Department::find($id); 
        if($department) {
            return response()->json([
                'message' => 'Xem chi tiết khoa thành công !',
                'department' => $department
            ], 201);
        }
        else {
            return response()->json([
                'message' => 'Không tìm thấy khoa !',
                'department' => $department
            ], 404);
        }
    }
}