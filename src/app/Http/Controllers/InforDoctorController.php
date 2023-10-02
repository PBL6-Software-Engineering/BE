<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\UserEnum;
use App\Http\Requests\RequestCreateInforHospital;
use App\Http\Requests\RequestChangePassword;
use App\Http\Requests\RequestCreateInforUser;
use App\Http\Requests\RequestCreateNewDoctor;
use App\Http\Requests\RequestCreatePassword;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use SebastianBergmann\Environment\Console;
use Exception;
use Mail;        
use Illuminate\Support\Facades\DB;
use App\Mail\SendPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory ;

use App\Http\Requests\RequestCreateUser;
use App\Http\Requests\RequestLogin;
use App\Http\Requests\RequestUpdateDoctor;
use App\Http\Requests\RequestUpdateHospital;
use App\Http\Requests\RequestUpdateInfor;
use App\Http\Requests\RequestUpdateUser;
use App\Jobs\SendForgotPasswordEmail;
use App\Jobs\SendMailNotify;
use App\Jobs\SendVerifyEmail;
use App\Models\InforDoctor;
use App\Models\InforHospital;
use App\Models\InforUser;
use App\Models\PasswordReset;
use App\Rules\ReCaptcha;
use App\Services\UserService;
use Database\Factories\FakeImageFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class InforDoctorController extends Controller
{
    public function refresh()
    {
        return $this->respondWithToken(auth('user_api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('user_api')->factory()->getTTL() * 60
        ]);
    }

    public function saveAvatar(Request $request){
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $filename =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_doctor_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/avatars/doctors/', $filename);
            return 'storage/image/avatars/doctors/' . $filename;
        }
    }

    public function profile()
    {
        $user = User::find(auth('user_api')->user()->id);
        $inforUser = InforDoctor::where('id_doctor', $user->id)->first();

        return response()->json([
            'doctor' => array_merge($user->toArray(), $inforUser->toArray()),
        ]);
    }

    public function updateProfile(RequestUpdateDoctor $request, $id_user)
    {
        $user = User::find(auth('user_api')->user()->id);
        $oldEmail = $user->email;
        if($request->hasFile('avatar')) {
            if ($user->avatar) {
                File::delete($user->avatar);
            }
            $avatar = $this->saveAvatar($request);
            $user->update(array_merge($request->all(),['avatar' => $avatar]));
        } else {
            $user->update($request->all());
        }

        $inforDoctor = InforDoctor::where('id_doctor', $user->id)->first();
        // $oldHospital = InforHospital::find($inforDoctor->id_hospital);
        // $emailOldHospital = User::find($oldHospital->id_hospital)->email;
        // $newHospital = InforHospital::find($request->id_hospital);
        // $emailNewHospital = User::find($newHospital->id_hospital)->email;
        $inforDoctor->update($request->all());
        $message = 'Cập nhật thông tin bác sĩ thành công !';

        // sendmail verify
        if($oldEmail != $request->email) {
            $token = Str::random(32);
            $url =  UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
            Queue::push(new SendVerifyEmail($user->email, $url));
            $new_email = $user->email;
            $content = 'Email tài khoản của bạn đã chuyển thành ' . $new_email . ' Nếu bạn không phải là người thực hiện , hãy liên hệ với quản trị viên của hệ thống để được hỗ trợ . ';
            Queue::push(new SendMailNotify($oldEmail, $content));
            $user->update([
                'token_verify_email' => $token,
                'email_verified_at' => null,
            ]);
            $message = 'Cập nhật thông tin thành công . Một mail xác nhận đã được gửi đến cho bạn , hãy kiểm tra và xác nhận nó !';
        } 
        // sendmail verify

        return response()->json([
            'message' => $message,
            'hospital' => array_merge($user->toArray(), $inforDoctor->toArray()),
        ], 201);
    }
}
