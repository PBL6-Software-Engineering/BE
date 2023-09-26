<?php

namespace App\Http\Controllers;

use App\Enums\UserEnum;
use App\Http\Requests\RequestCreateInforHospital;
use App\Http\Requests\RequestChangePassword;
use App\Http\Requests\RequestCreateInforUser;
use App\Http\Requests\RequestCreateNewDoctor;
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
use Faker\Factory ;
use GuzzleHttp\Client;

use App\Http\Requests\RequestCreateUser;
use App\Http\Requests\RequestLogin;
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

            // verify email 
            $token = Str::random(32);
            $url =  UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
            Queue::push(new SendVerifyEmail($user->email, $url));
            $user->update(['token_verify_email' => $token]);
            // verify email 

            return response()->json([
                'message' => 'Hospital successfully registered',
                'hospital' => array_merge($user->toArray(), $inforUser->toArray()),
            ], 201);
        }
    }

    public function profile()
    {
        $user = User::find(auth('user_api')->user()->id);
        $inforUser = InforHospital::where('id_hospital', $user->id)->first();

        return response()->json([
            'hospital' => array_merge($user->toArray(), $inforUser->toArray()),
        ]);
    }

    public function updateProfile(RequestUpdateHospital $request, $id_user)
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
        
        $inforHospital = InforHospital::where('id_hospital', $user->id)->first();
        $inforHospital->update($request->all());
        $message = 'Hospital successfully updated';

        // sendmail verify
        if($oldEmail != $request->email) {
            $token = Str::random(32);
            $url =  UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
            Queue::push(new SendVerifyEmail($user->email, $url));
            $new_email = $user->email;
            $content = 'Your account has been transferred to email ' . $new_email . ' If you are not the one making the change, please contact your system administrator for assistance. ';
            Queue::push(new SendMailNotify($oldEmail, $content));
            $user->update([
                'token_verify_email' => $token,
                'email_verified_at' => null,
            ]);
            $message = 'Hospital successfully updated . A confirmation email has been sent to this email, please check and confirm !';
        } 
        // sendmail verify

        return response()->json([
            'message' => $message,
            'hospital' => array_merge($user->toArray(), $inforHospital->toArray()),
        ], 201);
    }

    public function addDoctor(RequestCreateNewDoctor $request)
    {
        try {
            $hospital = User::find(auth('user_api')->user()->id);

            // Cách 1 dùng Factory 
            // $fakeImageFactory = FakeImageFactory::new();
            // $avatar = $fakeImageFactory->createAvatarDoctor();
            // while (!$avatar) {
            //     $avatar = $fakeImageFactory->createAvatarDoctor();
            // }
            // $avatar = 'storage/image/avatars/doctors/' . $avatar;

            // Cách 2 dùng "guzzlehttp/guzzle": "^7.8"
            try {
                $pathFolder = 'storage/image/avatars/doctors';
                if (!File::exists($pathFolder)) {
                    File::makeDirectory($pathFolder, 0755, true);
                }
                $client = new Client();
                $response = $client->get('https://picsum.photos/200/200');
                $imageContent = $response->getBody()->getContents();
                $pathFolder = 'storage/image/avatars/doctors/';
                $nameImage = uniqid() . '.jpg';
                $avatar = $pathFolder . $nameImage;
                file_put_contents($avatar, $imageContent);
            } catch (\Exception $e) {
                $avatar = null;
            }
            $new_password = Str::random(10);
            $doctor = User::create([
                'email' => $request->email,
                'password' => Hash::make($new_password),
                'name' => $request->name,
                'avatar' => $avatar,
                'is_accept' => true,
                'role' => 'doctor',
                'token_verify_email' => null,
                'email_verified_at' => now(),
            ]);
            InforDoctor::create([
                'id_doctor' => $doctor->id,
                'id_department' => $request->id_department,
                'id_hospital' => $hospital->id,
                'is_confirm' => true,
                'province_code' => $request->province_code
            ]);
            $content = 'This is your doctor account information , use it to log in to the system, then you should change your password for security <br> email: ' . $doctor->email . ' <br> password: ' . $new_password;
            Queue::push(new SendMailNotify($doctor->email, $content));
            return response()->json([
                'message' => "Add Doctor Success !",
            ],200);
        }
        catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
