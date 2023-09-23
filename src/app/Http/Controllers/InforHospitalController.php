<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateInforHospital;
use App\Http\Requests\RequestChangePassword;
use App\Http\Requests\RequestCreateInforUser;
use App\Http\Requests\RequestCreatePassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use SebastianBergmann\Environment\Console;
use Exception;
use Mail;        
use Illuminate\Support\Facades\DB;
use App\Mail\SendPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Http\Requests\RequestCreateUser;
use App\Http\Requests\RequestLogin;
use App\Http\Requests\RequestUpdateInfor;
use App\Http\Requests\RequestUpdateUser;
use App\Jobs\SendForgotPasswordEmail;
use App\Models\InforHospital;
use App\Models\InforUser;
use App\Models\PasswordReset;
use App\Rules\ReCaptcha;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class InforHospitalController extends Controller
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
        // $pathToFile = $request->file('avatar')->store('image/avatars','public');
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $filename =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_hospital_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/avatars/hospitals/', $filename);
            return 'storage/image/avatars/hospitals/' . $filename;
            // public/image/avatars/users : giả sử folder này chưa có thì nó tự động tạo folder này luôn . Mình không cần tự tạo . 
        }
    }

    public function register(RequestCreateInforHospital $request)
    {
        $userEmail = User::where('email', $request->email)->where('role', 'hospital')->first();
        if($userEmail){
            return response()->json(['error' => 'Account already exists !'], 401);
        }
        else {
            $avatar = $this->saveAvatar($request);
            $user = User::create(array_merge(
                $request->all(),
                ['password' => Hash::make($request->password), 'is_accept'=> 0, 'role'=> 'hospital', 'avatar' => $avatar]
            ));

            $inforUser = InforHospital::create(array_merge(
                $request->all(),
                ['id_hospital' => $user->id]
            ));

            return response()->json([
                'message' => 'Hospital successfully registered',
                'hospital' => array_merge($user->toArray(), $inforUser->toArray()),
            ], 201);
        }
    }
}
