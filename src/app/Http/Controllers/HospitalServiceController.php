<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateHospitalService;
use App\Http\Requests\RequestUpdateHospitalService;
use App\Models\Department;
use App\Models\HospitalDepartment;
use App\Models\HospitalService;
use App\Models\InforDoctor;
use App\Models\InforHospital;
use App\Models\Product;
use App\Models\WorkSchedule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;

class HospitalServiceController extends Controller
{
    public function add(RequestCreateHospitalService $request){
        $user = auth()->guard('user_api')->user();
        $hospitalDepartment = HospitalDepartment::where('id',$request->id_hospital_department)
        ->where('id_hospital', $user->id)->first();
        if(empty($hospitalDepartment)) return response()->json(['message' => 'Not found Hospital Department !',], 404);
        
        $request->merge(['infor' => json_encode($request->infor)]);
        $hospitalService = HospitalService::create($request->all());
        
        return response()->json([
            'message' => 'Add Hospital Service for Hospital successfully ',
            'hospital_service' => $hospitalService
        ], 201);
    }

    public function edit(RequestUpdateHospitalService $request,$id){
        try {
            $user = auth()->guard('user_api')->user();
            $hospitalService = HospitalService::find($id);
            $hospitalDepartment = HospitalDepartment::where('id',$hospitalService->id_hospital_department)
            ->where('id_hospital', $user->id)->first();
            if(empty($hospitalDepartment)) {
                return response()->json(['message' => 'Not found hospital department !',], 404);
            }

            $hospitalDepartment = HospitalDepartment::where('id',$request->id_hospital_department)
            ->where('id_hospital', $user->id)->first();
            if(empty($hospitalDepartment)) return response()->json(['message' => 'Not found Hospital Department !',], 404);

            $request->merge(['infor' => json_encode($request->infor)]);
            $hospitalService->update($request->all());

            return response()->json([
                'message' => 'Update Service for Hospital successfully ',
                'hospital_service' => $hospitalService
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' =>  $e->getMessage()], 401);
        }
    }

    public function delete($id)
    {
        $user = auth()->guard('user_api')->user();
        try {
            $hospitalService =  HospitalService::find($id);
            if ($hospitalService) {
                $hospitalDepartment = HospitalDepartment::where('id',$hospitalService->id_hospital_department)
                ->where('id_hospital', $user->id)->first();
                if(empty($hospitalDepartment)) {
                    return response()->json(['message' => 'Bạn không có quyền xóa !',], 404);
                }
                // kiểm tra có workSchedule có id_service là nó không 
                // nếu có thì workSchedule đã được làm chưa (time của workSchedule nhỏ hơn thời gian hiện tại là được)
                // sau đó mới xóa . tạm thời cứ check có hay chưa đã 
                // khi nào làm đến bảng WorkSchedule thì quay lại 
                $count = WorkSchedule::where('id_service',$hospitalService->id)->count();
                if($count > 0) {
                    return response()->json(['message' => 'Dịch vụ này đang được đặt , bạn không được xóa nó !',], 400);
                }
                $hospitalService->delete();
                return response()->json([
                    'message' => 'Delete hospital Service successfully',
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Not found hospital service !',
                ], 404);
            }
        } catch (Exception $e) {
            return response()->json(['error' =>  $e->getMessage()], 401);
        }
    }

    public function serviceOfHospital(Request $request)
    {
        $user = auth()->guard('user_api')->user();
        if ($request->paginate == true) { // lấy cho department 
            $search = $request->search;
            $orderBy = 'id_hospital_service';
            $orderDirection = 'ASC';
        
            if ($request->sortlatest == 'true') {
                $orderBy = 'id_hospital_service';
                $orderDirection = 'DESC';
            }
        
            if ($request->sortname == 'true') {
                $orderBy = 'name';
                $orderDirection = ($request->sortlatest == 'true') ? 'DESC' : 'ASC';
            }
        
            $hospitalServices = HospitalService::orderBy($orderBy, $orderDirection)
            ->join('hospital_departments', 'hospital_departments.id', '=', 'hospital_services.id_hospital_department')
            ->where('name', 'LIKE', '%' . $search . '%')
            ->where('id_hospital',$user->id)
            ->select(
                'hospital_services.id as id_hospital_service', 'hospital_departments.id as id_hospital_departments',
                'hospital_services.time_advise as time_advise_hospital_service', 'hospital_departments.time_advise as time_advise_hospital_departments',
                'hospital_services.price as price_hospital_service', 'hospital_departments.price as price_hospital_departments',
                'hospital_services.*', 'hospital_departments.*')
            ->paginate(6);

            return response()->json([
                'message' => 'Get all hospital Services successfully !',
                'hospital_services' => $hospitalServices,
            ], 201);
        }
        else { // lấy cho product 
            $hospitalServices = HospitalService::join('hospital_departments', 'hospital_departments.id', '=', 'hospital_services.id_hospital_department')
            ->where('id_hospital',$user->id)
            ->select(
                'hospital_services.id as id_hospital_service', 'hospital_departments.id as id_hospital_departments',
                'hospital_services.time_advise as time_advise_hospital_service', 'hospital_departments.time_advise as time_advise_hospital_departments',
                'hospital_services.price as price_hospital_service', 'hospital_departments.price as price_hospital_departments',
                'hospital_services.*', 'hospital_departments.*')
            ->paginate(6);
            return response()->json([
                'message' => 'Get all hospital Services successfully !',
                'hospital_services' => $hospitalServices,
            ], 201);
        }
    }

    public function details(Request $request, $id){
        $user = auth()->guard('user_api')->user();
        $hospitalServices = HospitalService::join('hospital_departments', 'hospital_departments.id', '=', 'hospital_services.id_hospital_department')
        ->where('id_hospital',$user->id)
        ->where('hospital_services.id',$id)
        ->select(
            'hospital_services.id as id_hospital_service', 'hospital_departments.id as id_hospital_departments',
            'hospital_services.time_advise as time_advise_hospital_service', 'hospital_departments.time_advise as time_advise_hospital_departments',
            'hospital_services.price as price_hospital_service', 'hospital_departments.price as price_hospital_departments',
            'hospital_services.*', 'hospital_departments.*')
        ->first();
        if($hospitalServices) {
            return response()->json([
                'message' => 'Get hospital service details successfully !',
                'hospital_service' => $hospitalServices
            ], 201);
        }
        else {
            return response()->json([
                'message' => 'Not found hospital service !',
            ], 404);
        }

    }
}
