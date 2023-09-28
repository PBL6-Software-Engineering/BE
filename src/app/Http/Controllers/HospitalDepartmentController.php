<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateHospitalDepartment;
use App\Http\Requests\RequestUpdateHospitalDepartment;
use App\Models\Department;
use App\Models\HospitalDepartment;
use Illuminate\Http\Request;

class HospitalDepartmentController extends Controller
{
    public function add(RequestCreateHospitalDepartment $request)
    {
        $user = auth()->guard('user_api')->user();
        $department = Department::find($request->id_department);
        if(empty($department)) return response()->json(['message' => 'Not found department !',], 404);
       
        $hospitalDepartment = HospitalDepartment::create(array_merge(
            $request->all(),
            [ 
                'id_hospital' => $user->id
            ]
        ));
        return response()->json([
            'message' => 'Add Department for Hospital successfully ',
            'hospital_department' => $hospitalDepartment
        ], 201);
    }

    public function edit(RequestUpdateHospitalDepartment $request, $id)
    {
        $user = auth()->guard('user_api')->user();
        $hospitalDepartment = HospitalDepartment::find($id); 
        if(empty($hospitalDepartment)) {
            return response()->json(['message' => 'Not found hospital department !',], 404);
        }

        if($user->id != $hospitalDepartment->id_hospital) {
            return response()->json(['message' => 'Forbidden !',], 403);
        }

        $hospitalDepartment->update($request->all());
        return response()->json([
            'message' => 'Update Department for Hospital successfully ',
            'hospital_department' => $hospitalDepartment
        ], 201);
    }

    public function delete($id)
    {
        $user = auth()->guard('user_api')->user();
        $hospitalDepartment = HospitalDepartment::find($id); 
        if(empty($hospitalDepartment)) {
            return response()->json(['message' => 'Not found hospital department !',], 404);
        }

        if($user->id != $hospitalDepartment->id_hospital) {
            return response()->json(['message' => 'Forbidden !',], 403);
        }

        $hospitalDepartment->delete();
        return response()->json([
            'message' => 'Delete Department for Hospital successfully ',
        ], 201);
    }

    public function departmentOfHospital(){
        $user = auth()->guard('user_api')->user();
        $hospitalDepartments = HospitalDepartment::join('departments', 'hospital_departments.id_department', '=', 'departments.id')
            ->where('hospital_departments.id_hospital', $user->id)
            ->select('hospital_departments.*', 'departments.*')
            ->get();

        return response()->json([
            'message' => 'Get All Department of Hospital successfully ',
            'hospital_departments' => $hospitalDepartments
        ], 201);
    }

    public function details($id) {
        $hospitalDepartment = HospitalDepartment::join('departments', 'hospital_departments.id_department', '=', 'departments.id')
            ->where('hospital_departments.id', $id)
            ->select('hospital_departments.*', 'departments.*')
            ->first();

        if(empty($hospitalDepartment)) {
            return response()->json(['message' => 'Not found hospital department !',], 404);
        }
        return response()->json([
            'message' => 'Get All Department of Hospital successfully ',
            'hospital_departments' => $hospitalDepartment
        ], 201);
    }
}
