<?php

namespace App\Http\Requests;

use App\Rules\UniqueHospitalDepartmentRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class RequestCreateHospitalDepartment extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $id_hospital = Auth::guard('user_api')->user()->id;
        return [
            'id_department' => [
                'required', 
                'integer', 
                new UniqueHospitalDepartmentRule($this->id_department, $id_hospital)
            ],
            'time_advise' => 'required|integer',
            'price' => 'required|numeric', // Sử dụng kiểu dữ liệu số thực
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));

    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'body.required' => 'Body is required'
        ];
    }
}
