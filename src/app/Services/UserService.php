<?php

namespace App\Services;

use App\Enums\UserEnum;
use App\Jobs\SendForgotPasswordEmail;
use App\Jobs\SendMailWelCome;
use App\Mail\ForgotPassword;
use App\Mail\NotifyMail;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserInterface;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class UserService
{
    protected UserInterface $userRepository;

    public function __construct(
        UserInterface $userRepository,
    ) {
        $this->userRepository = $userRepository;
    }

    /**
     * userLogin
     *
     * @param object $filter
     */
    public function userLogin(object $filter)
    {
        try {
            if (strpos($filter->email, '@') !== false) {
                $credentials = [
                    'email' => $filter->email,
                    'password' => $filter->password,
                ];
                if (Auth::guard('user')->attempt($credentials)) {
                    if (Auth::guard('user')->user()->status == 0) {
                        Toastr::error('Your account has been locked or not approved !');
                        Auth::guard('user')->logout();

                        return redirect()->back()->withInput();
                    } else {
                        Toastr::success('Login successful !');

                        return redirect()->route('infor.view_infor');
                    }
                }
                Toastr::error('Login details are not valid !');

                return redirect()->back()->withInput();
            } else {
                $user = $this->userRepository->getUser($filter->email);
                if ($user && (Hash::check($filter->password, $user->password))) {
                    if ($user->status == 0) {
                        Toastr::error('Your account has been locked or not approved !');

                        return redirect()->back()->withInput();
                    } else {
                        Auth::guard('user')->login($user);
                        Toastr::success('Login successful !');

                        return redirect()->route('infor.view_infor');
                    }
                } else {
                    Toastr::error('Login details are not valid !');

                    return redirect()->back()->withInput();
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * saveAvatar
     *
     * @param object $filter
     * @return string
     */
    public function saveAvatar(object $filter)
    {
        try {
            if ($filter->image) {
                $image = $filter->image;
                $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.' . $image->getClientOriginalExtension();
                $image->storeAs(UserEnum::PATH_FILE_SAVE, $filename);

                return UserEnum::PATH_FILE_DB . $filename;
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * sendMail
     *
     * @param object $user
     * @return response
     */
    public function sendMail($user)
    {
        try {
            // Mail::to($user->email)->send(new NotifyMail($user->name));
            Log::info("Add jobs to Queue , Email: $user->email with URL: $user->name");
            // for($i=0; $i<1000; $i++) {
            //     Queue::push(new SendMailWelCome($user->email, $user->name));
            // }
            Queue::push(new SendMailWelCome($user->email, $user->name));
            Toastr::success('Send Mail Success !');
        } catch (\Exception $e) {
            Toastr::error('Send Mail Error !');
        }

        return response();
    }

    /**
     * userRegister
     *
     * @param object $filter
     */
    public function userRegister(object $filter)
    {
        try {
            if (strpos($filter->username, '@') !== false) {
                Toastr::error('Username cannot contain the @ character !');

                return redirect()->back()->withInput();
            }
            $user = $this->userRepository->getUserByUsername($filter);
            if ($user) {
                Toastr::error('Username already exists !');

                return redirect()->back()->withInput();
            }
            $userEmail = $this->userRepository->findUserbyEmail($filter->email);
            if ($userEmail) {
                if ($userEmail['password']) {
                    Toastr::error('Account already exists !');

                    return redirect()->back()->withInput();
                } else {
                    $filter->avatar = $this->saveAvatar($filter);
                    $this->userRepository->updateUser($userEmail, $filter);
                }
            } else {
                $filter->avatar = $this->saveAvatar($filter);
                $user = $this->userRepository->createUser($filter);
                Toastr::success('Register successful !');
                $this->sendMail($user);
            }

            return redirect()->back();
        } catch (\Exception $e) {
        }
    }

    /**
     * handleGithubCallback
     *
     * @param object $user
     */
    public function handleGithubCallback($user)
    {
        try {
            $finduser = $this->userRepository->findUserByGithubId($user->id);
            if ($finduser) {
                Auth::guard('user')->login($finduser);

                if (Auth::guard('user')->user()->status == 0) {
                    Toastr::error('Your account has been locked or not approved !');

                    Auth::guard('user')->logout();

                    return redirect()->route('login');
                }

                return redirect()->route('infor.view_infor');
            } else {
                $findEmail = $this->userRepository->findUserbyEmail($user->email);
                if ($findEmail) {
                    $this->userRepository->updateIdGithub($findEmail, $user->id);
                    Auth::guard('user')->login($findEmail);
                    Toastr::success('Login successful !');
                } else {
                    $newUser = $this->userRepository->createUserGithub($user);
                    Auth::guard('user')->login($newUser);
                    Toastr::success('Register successful !');
                    $this->sendMail($newUser);
                }

                return redirect()->route('infor.view_infor');
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * handleGoogleCallback
     *
     * @param object $user
     */
    public function handleGoogleCallback($user)
    {
        try {
            $finduser = $this->userRepository->findUserByGoogleId($user->id);
            if ($finduser) {
                Auth::guard('user')->login($finduser);

                if (Auth::guard('user')->user()->status == 0) {
                    Toastr::error('Your account has been locked or not approved !');

                    Auth::guard('user')->logout();

                    return redirect()->route('login');
                }

                return redirect()->route('infor.view_infor');
            } else {
                $findEmail = $this->userRepository->findUserbyEmail($user->email);
                if ($findEmail) {
                    $this->userRepository->updateIdGoogle($findEmail, $user->id);
                    Auth::guard('user')->login($findEmail);
                    Toastr::success('Login successful !');
                } else {
                    $newUser = $this->userRepository->createUserGoogle($user);
                    Auth::guard('user')->login($newUser);
                    Toastr::success('Register successful !');
                    $this->sendMail($newUser);
                }

                return redirect()->route('infor.view_infor');
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * forgotSend
     *
     * @param string $email
     */
    public function forgotSend($email)
    {
        try {
            $token = Str::random(32);
            $is_user = 1;
            $user = PasswordResetRepository::findPasswordReset($email, $is_user);
            if ($user) {
                PasswordResetRepository::updateToken($user, $token);
            } else {
                PasswordResetRepository::createToken($email, $token, $is_user);
            }
            Toastr::success('Send Mail Password Reset Success !');
            $url = 'http://localhost:8000/forgot-form?token=' . $token;
            // Mail::to($email)->send(new ForgotPassword($url));
            // SendForgotPasswordEmail::dispatch($email, $url);
            Log::info("Add jobs to Queue , Email: $email with URL: $url");
            Queue::push(new SendForgotPasswordEmail($email, $url));

            return redirect()->back();
        } catch (\Exception $e) {
        }
    }

    /**
     * forgotUpdate
     *
     * @param object $filter
     */
    public function forgotUpdate($filter)
    {
        try {
            $is_user = 1;
            $new_password = Hash::make($filter->new_password);
            $userReset = PasswordResetRepository::findPasswordResetByToken($filter->token);
            if ($userReset) {
                if ($filter->new_password != $filter->confim_new_password) {
                    Toastr::error('New password and confirm new password do not match !');

                    return redirect()->back()->withInput();
                }
                $user = $this->userRepository->findUserbyEmail($userReset->email);
                if ($user) {
                    $this->userRepository->updatePassword($user, $new_password);
                    Toastr::success('Reset Password successful !');
                    PasswordResetRepository::deleteUser($userReset, $is_user);

                    return redirect('login');
                }
                Toastr::error('Can not find the account !');

                return redirect('register');
            } else {
                Toastr::error('Token has expired !');

                return redirect('register');
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * updateInfor
     *
     * @param $filter
     */
    public function updateInfor($filter)
    {
        $user = $this->userRepository->findUserById(Auth::guard('user')->user()->id);
        $filter->avatar = null;
        if ($filter->image) {
            $filter->avatar = $this->saveAvatar($filter);
            if (!Str::startsWith($user->avatar, 'http')) {
                if ($user->avatar) {
                    File::delete($user->avatar);
                }
            }
        }
        try {
            $user = $this->userRepository->updateInfor($user, $filter);
            Toastr::success('Successfully updated !');

            return redirect()->route('infor.view_infor');
        } catch (Exception $e) {
            Toastr::error('Update failed !');

            return redirect()->back()->withInput();
        }
    }

    /**
     * changePassword
     *
     * @param $filter
     */
    public function changePassword($filter)
    {
        try {
            $user = $this->userRepository->findUserById(Auth::guard('user')->user()->id);
            if ($user->password == null) {
                $this->userRepository->updatePassword($user, Hash::make($filter->new_password));
            } else {
                if ($filter->old_password != $filter->confirm_old_password) {
                    Toastr::error('Old password and confirm old password do not match !');

                    return redirect()->back()->withInput();
                }

                if (!Hash::check($filter->old_password, $user->password)) {
                    Toastr::error('Old password is incorrect !');

                    return redirect()->back()->withInput();
                }
                $this->userRepository->updatePassword($user, Hash::make($filter->new_password));
            }
            Toastr::success('Updated password successfully !');

            return redirect()->route('infor.view_infor');
        } catch (\Exception $e) {
        }
    }

    /**
     * allUser
     *
     * @return array
     */
    public function allUser()
    {
        return $this->userRepository->getAllUser()->paginate(6);
    }

    /**
     * changeStatus
     *
     * @param int $id_user
     */
    public function changeStatus($id_user)
    {
        try {
            $new_status = $this->userRepository->changeStatus($id_user);
            if ($new_status == 0 && $id_user == Auth::guard('user')->user()->id) {
                // Session::flush();
                Auth::guard('user')->logout();
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * ajaxSearchUserAdmin
     *
     * @return object
     */
    public function ajaxSearchUserAdmin($search_text)
    {
        try {
            $users = $this->userRepository->ajaxSearchUser($search_text)->paginate(6);
            $render_html = view('admin.render_ajax.user', compact('users'))->render();
            $pagination = $users->links()->toHtml();

            return response()->json([
                'render_html' => $render_html,
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
        }
    }
}
