<?php

namespace App\Services;

use App\Enums\UserEnum;
use App\Jobs\SendForgotPasswordEmail;
use App\Jobs\SendPasswordNewAdmin;
use App\Mail\ForgotPassword;
use App\Repositories\AdminInterface;
use App\Repositories\PasswordResetRepository;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class AdminService
{
    protected AdminInterface $adminRepository;

    public function __construct(
        AdminInterface $adminRepository,
    ) {
        $this->adminRepository = $adminRepository;
    }

    /**
     * adminLogin
     *
     * @param object $filter
     */
    public function adminLogin(object $filter)
    {
        $credentials = [
            'email' => $filter->email,
            'password' => $filter->password,
        ];
        if (Auth::guard('admin')->attempt($credentials)) {
            Toastr::success('Login successful !');

            return redirect()->route('admin.view_infor');
        }
        Toastr::error('Login details are not valid !');

        return redirect()->back()->withInput();
    }

    /**
     * allAdmin
     *
     * @return array
     */
    public function allAdmin()
    {
        try {
            return $this->adminRepository->getAllAdmin()->paginate(6);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * addAdmin
     */
    public function addAdmin($new_admin)
    {
        try {
            $password = Str::random(10);
            $new_admin->password = Hash::make($password);

            Queue::push(new SendPasswordNewAdmin($new_admin->email, $password));
            $this->adminRepository->addAdmin($new_admin);
            Toastr::success('Admin added successfully');

            return redirect()->back();
        } catch (\Exception $e) {
            Toastr::error($e->getMessage());

            return redirect()->back();
        }
    }

    /**
     * changeRole
     *
     * @param object
     */
    public function changeRole($filter)
    {
        try {
            $this->adminRepository->changeRole($filter);
        } catch (\Exception $e) {
        }
    }

    /**
     * deleteAdmin
     *
     * @param int $id_admin
     */
    public function deleteAdmin($id_admin)
    {
        try {
            $admin = $this->adminRepository->getAdminById($id_admin);
            $admin->delete();
            Toastr::success('Delete admin successfully !');
        } catch (\Exception $e) {
            Toastr::success('Delete admin fail !');
        }

        return redirect()->back();
    }

    /**
     * ajaxSearchInforAdmin
     *
     * @param string $search
     * @param string role
     */
    public function ajaxSearchInforAdmin($search, $role)
    {
        try {
            $admins = $this->adminRepository->ajaxSearchInforAdmin($search, $role)->paginate(6);
            $render_html = view('admin.render_ajax.admin', compact('admins'))->render();
            $pagination = $admins->links()->toHtml();

            return response()->json([
                'render_html' => $render_html,
                'pagination' => $pagination,
            ]);
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
     * updateInfor
     *
     * @param $filter
     */
    public function updateInfor($filter)
    {
        $admin = $this->adminRepository->findAdminById(Auth::guard('admin')->user()->id);
        $filter->avatar = null;
        if ($filter->image) {
            $filter->avatar = $this->saveAvatar($filter);
            if ($admin->avatar != 'storage/Blog/image/avatars/admin.png') {
                if ($admin->avatar) {
                    File::delete($admin->avatar);
                }
            }
        }
        try {
            $admin = $this->adminRepository->updateInfor($admin, $filter);
            Toastr::success('Successfully updated !');

            return redirect()->route('admin.view_infor');
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
            $admin = $this->adminRepository->findAdminById(Auth::guard('admin')->user()->id);
            if ($admin->password == null) {
                $this->adminRepository->updatePassword($admin, Hash::make($filter->new_password));
            } else {
                if ($filter->old_password != $filter->confirm_old_password) {
                    Toastr::error('Old password and confirm old password do not match !');

                    return redirect()->back()->withInput();
                }

                if (!Hash::check($filter->old_password, $admin->password)) {
                    Toastr::error('Old password is incorrect !');

                    return redirect()->back()->withInput();
                }
                $this->adminRepository->updatePassword($admin, Hash::make($filter->new_password));
            }
            Toastr::success('Updated password successfully !');

            return redirect()->route('admin.view_infor');
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
            $is_user = 0;
            $admin = PasswordResetRepository::findPasswordReset($email, $is_user);
            if ($admin) {
                PasswordResetRepository::updateToken($admin, $token);
            } else {
                PasswordResetRepository::createToken($email, $token, $is_user);
            }
            Toastr::success('Send Mail Password Reset Success !');
            $url = 'http://localhost:8000/admin/forgot-form?token=' . $token;
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
            $is_user = 0;
            $new_password = Hash::make($filter->new_password);
            $adminReset = PasswordResetRepository::findPasswordResetByToken($filter->token);
            if ($adminReset) {
                if ($filter->new_password != $filter->confim_new_password) {
                    Toastr::error('New password and confirm new password do not match !');

                    return redirect()->back()->withInput();
                }
                $admin = $this->adminRepository->findAdminbyEmail($adminReset->email);
                if ($admin) {
                    $this->adminRepository->updatePassword($admin, $new_password);
                    Toastr::success('Reset Password successful !');
                    PasswordResetRepository::deleteUser($adminReset, $is_user);

                    return redirect('admin/login');
                }
                Toastr::error('Can not find the account !');

                return redirect('register');
            } else {
                Toastr::error('Token has expired !');

                return redirect('admin/login');
            }
        } catch (\Exception $e) {
        }
    }
}
