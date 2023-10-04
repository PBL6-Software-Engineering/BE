<?php

namespace App\Services;

use App\Enums\UserEnum;
use App\Http\Requests\RequestCreateInforHospital;
use App\Http\Requests\RequestCreateNewDoctor;
use App\Http\Requests\RequestUpdateHospital;
use App\Jobs\SendMailNotify;
use App\Jobs\SendVerifyEmail;
use App\Repositories\InforDoctorRepository;
use App\Repositories\InforHospitalInterface;
use App\Repositories\InforHospitalRepository;
use App\Repositories\UserRepository;
use Database\Factories\FakeImageFactory;
use Faker\Factory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class InforHospitalService
{
    protected InforHospitalInterface $inforHospitalRepository;

    public function __construct(
        InforHospitalInterface $inforHospitalRepository
    ) {
        $this->inforHospitalRepository = $inforHospitalRepository;
    }

    public function saveAvatar(Request $request)
    {
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_hospital_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/image/avatars/hospitals/', $filename);

            return 'storage/image/avatars/hospitals/' . $filename;
        }
    }

    public function register(RequestCreateInforHospital $request)
    {
        try {
            $filter = [
                'email' => $request->email ?? '',
                'role' => 'hospital',
            ];
            $userEmail = UserRepository::findUser($filter)->first();
            if ($userEmail) {
                return response()->json(['message' => 'Tài khoản đã tồn tại !'], 400);
            } else {
                $avatar = $this->saveAvatar($request);
                $data = array_merge(
                    $request->all(),
                    ['password' => Hash::make($request->password), 'is_accept' => 0, 'role' => 'hospital', 'avatar' => $avatar]
                );
                $user = UserRepository::createUser($data);
                $request->merge([
                    'infrastructure' => json_encode($request->infrastructure),
                    'location' => json_encode($request->location),
                ]);

                $data = array_merge(
                    $request->all(),
                    ['id_hospital' => $user->id]
                );

                $inforUser = InforHospitalRepository::createHospital($data);

                // verify email
                $token = Str::random(32);
                $url = UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
                Queue::push(new SendVerifyEmail($user->email, $url));
                $data = ['token_verify_email' => $token];
                $user = UserRepository::updateUser($user->id, $data);
                // verify email

                $inforUser->infrastructure = json_decode($inforUser->infrastructure);
                $inforUser->location = json_decode($inforUser->location);

                return response()->json([
                    'message' => 'Đăng kí tài khoản thành công !',
                    'hospital' => array_merge($user->toArray(), $inforUser->toArray()),
                ], 201);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function profile()
    {
        try {
            $user = UserRepository::findUserById(auth('user_api')->user()->id);
            $inforUser = InforHospitalRepository::getInforHospital(['id_hospital' => $user->id])->first();
            $inforUser->infrastructure = json_decode($inforUser->infrastructure);
            $inforUser->location = json_decode($inforUser->location);

            return response()->json([
                'hospital' => array_merge($user->toArray(), $inforUser->toArray()),
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateProfile(RequestUpdateHospital $request, $id_user)
    {
        try {
            $user = UserRepository::findUserById(auth('user_api')->user()->id);
            $oldEmail = $user->email;

            $request->merge([
                'infrastructure' => json_encode($request->infrastructure),
                'location' => json_encode($request->location),
            ]);

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    File::delete($user->avatar);
                }
                $avatar = $this->saveAvatar($request);
                $data = array_merge($request->all(), ['avatar' => $avatar]);
                $user = UserRepository::updateUser($user->id, $data);
            } else {
                $user = UserRepository::updateUser($user->id, $request->all());
            }

            $inforHospital = InforHospitalRepository::getInforHospital(['id_hospital' => $user->id])->first();
            $inforHospital = InforHospitalRepository::updateInforHospital($inforHospital->id, $request->all());
            $message = 'Hospital successfully updated';

            // sendmail verify
            if ($oldEmail != $request->email) {
                $token = Str::random(32);
                $url = UserEnum::DOMAIN_PATH . 'verify-email/' . $token;
                Queue::push(new SendVerifyEmail($user->email, $url));
                $new_email = $user->email;
                $content = 'Email tài khoản của bạn đã được thay đổi thành ' . $new_email . ' Nếu bạn không phải là người thực hiện thay đổi này , hãy liên hệ với quản trị viên của hệ thống để được hỗ trợ ! ';
                Queue::push(new SendMailNotify($oldEmail, $content));
                $data = [
                    'token_verify_email' => $token,
                    'email_verified_at' => null,
                ];
                $user = UserRepository::updateUser($user->id, $data);
                $message = 'Cập nhật thông tin bệnh viện thành công . Một mail xác nhận đã được gửi đến cho bạn , hãy kiểm tra và xác nhận nó !';
            }
            // sendmail verify

            $inforHospital->infrastructure = json_decode($inforHospital->infrastructure);
            $inforHospital->location = json_decode($inforHospital->location);

            return response()->json([
                'message' => $message,
                'hospital' => array_merge($user->toArray(), $inforHospital->toArray()),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function addDoctor(RequestCreateNewDoctor $request)
    {
        try {
            $hospital = UserRepository::findUserById(auth('user_api')->user()->id);

            // Cách 1 dùng Factory
            // $fakeImageFactory = FakeImageFactory::new();
            // $avatar = $fakeImageFactory->createAvatarDoctor();
            // while (!$avatar) {
            //     $avatar = $fakeImageFactory->createAvatarDoctor();
            // }
            // $avatar = 'storage/image/avatars/doctors/' . $avatar;

            // Cách 2 dùng "guzzlehttp/guzzle": "^7.8"
            $avatar = null;
            $pathFolder = 'storage/image/avatars/doctors';
            if (!File::exists($pathFolder)) {
                File::makeDirectory($pathFolder, 0755, true);
            }
            $client = new Client;
            while (true) {
                try {
                    $response = $client->get('https://picsum.photos/200/200');
                    $imageContent = $response->getBody()->getContents();
                    $pathFolder = 'storage/image/avatars/doctors/';
                    $nameImage = uniqid() . '.jpg';
                    $avatar = $pathFolder . $nameImage;
                    file_put_contents($avatar, $imageContent);
                    if (file_exists($avatar)) {
                        break;
                    }
                } catch (Throwable $e) {
                }
            }

            $new_password = Str::random(10);
            $data = [
                'email' => $request->email,
                'password' => Hash::make($new_password),
                'name' => $request->name,
                'avatar' => $avatar,
                'is_accept' => true,
                'role' => 'doctor',
                'token_verify_email' => null,
                'email_verified_at' => now(),
            ];
            $doctor = UserRepository::createUser($data);

            $data = [
                'id_doctor' => $doctor->id,
                'id_department' => $request->id_department,
                'id_hospital' => $hospital->id,
                'is_confirm' => true,
                'province_code' => $request->province_code,
            ];
            $inforDoctor = InforDoctorRepository::createDoctor($data);

            $content = 'Dưới đây là thông tin tài khoản của bạn , hãy sử dụng nó để đăng nhập vào hệ thống , sau đó hãy tiến hành đổi mật khẩu để đảm bảo tính bảo mật cho tài khoản . <br> email: ' . $doctor->email . ' <br> password: ' . $new_password;
            Queue::push(new SendMailNotify($doctor->email, $content));

            return response()->json([
                'message' => 'Thêm tài khoản bác sĩ thành công !',
                'hospital' => array_merge($doctor->toArray(), $inforDoctor->toArray()),

            ], 200);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
