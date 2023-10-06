<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HealthInsuranceController;
use App\Http\Controllers\HospitalDepartmentController;
use App\Http\Controllers\HospitalServiceController;
use App\Http\Controllers\InforDoctorController;
use App\Http\Controllers\InforHospitalController;
use App\Http\Controllers\InforUserController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Admin
Route::prefix('admin')->controller(AdminController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('forgot-pw-sendcode', 'forgotSend');
    Route::middleware('auth:admin_api')->group(function () {
        Route::get('logout', 'logout');
        Route::get('profile', 'profile');
        Route::post('change-password', 'changePassword');
        Route::post('update/{admin}', 'updateProfile');
        Route::get('all-user', 'allUser');
        Route::post('change-accept/{id}', 'changeAccept');
    });
    // Route::middleware(['auth:admin_api','role_admin:superadmin,manager'])->group(function () {
    // Gộp chung role (users) và role_amdin (admins) thành role vì cả 2 bảng users và admins đều có cột role
    Route::middleware(['auth:admin_api', 'role:superadmin,manager'])->group(function () {
        Route::get('all-admin', 'allAdmin');
        Route::post('add-admin', 'addAdmin');
        Route::patch('{id}', 'editRole');
    });
    Route::middleware(['auth:admin_api', 'role:manager'])->group(function () {
        Route::delete('{id}', 'deleteAdmin');
    });
});

// User
Route::prefix('user')->controller(UserController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('forgot-pw-sendcode', 'forgotSend');
    Route::middleware('auth:user_api')->group(function () {
        Route::get('logout', 'logout');
        Route::post('change-password', 'changePassword');
        Route::get('infor-user/{id}', 'getInforUser');
    });
});

// User Infor
Route::prefix('infor-user')->controller(InforUserController::class)->group(function () {
    Route::post('register', 'register');
    Route::get('authorized/google', [InforUserController::class, 'redirectToGoogle'])->name('google');
    Route::get('authorized/google/callback', [InforUserController::class, 'handleGoogleCallback']);
    Route::middleware(['auth:user_api', 'role:user'])->group(function () {
        Route::post('create-password', 'createPassword');
        Route::post('update/{user}', 'updateProfile');
        Route::get('profile', 'profile');
        Route::post('create-password', 'createPassword');
    });
});

// Hospital Infor
Route::prefix('infor-hospital')->controller(InforHospitalController::class)->group(function () {
    Route::post('register', 'register');
    Route::middleware(['auth:user_api', 'role:hospital'])->group(function () {
        Route::post('update/{user}', 'updateProfile');
        Route::get('profile', 'profile');
        Route::post('add-doctor', 'addDoctor');
    });
});

// Doctor Infor
Route::prefix('infor-doctor')->controller(InforDoctorController::class)->group(function () {
    Route::middleware(['auth:user_api', 'role:doctor'])->group(function () {
        Route::post('update/{user}', 'updateProfile');
        Route::get('profile', 'profile');
        Route::post('add-doctor', 'addDoctor');
    });
});

// Category
Route::prefix('category')->controller(CategoryController::class)->group(function () {
    Route::middleware('auth:admin_api')->group(function () {
        Route::post('/add', 'add');
        Route::post('update/{id}', 'edit');
        Route::delete('delete/{id}', 'delete');
    });
    Route::get('/', 'all');
    Route::get('/detail/{id}', 'details');
});

// Article
Route::prefix('article')->controller(ArticleController::class)->group(function () {
    Route::middleware(['auth:admin_api,user_api', 'role:admin,superadmin,manager,doctor,hospital'])->group(function () {
        Route::post('/add', 'add');
        Route::post('update/{id}', 'edit');
    });

    Route::middleware(['auth:user_api', 'role:doctor,hospital'])->group(function () {
        Route::delete('delete/{id}', 'delete');
    });

    Route::middleware('auth:admin_api')->group(function () {
        Route::post('hide-show/{id}', 'hideShow');
        Route::post('change-accept/{id}', 'changeAccept');
    });

    Route::get('/', 'all');
    Route::get('/user/{id}', 'articleOfDoctorHospital');
    Route::get('/admin', 'articleOfAdmin');
    Route::get('/detail/{id}', 'details');
});

// Department
Route::prefix('department')->controller(DepartmentController::class)->group(function () {
    Route::middleware('auth:admin_api')->group(function () {
        Route::post('/add', 'add');
        Route::post('update/{id}', 'edit');
        Route::delete('/{id}', 'delete');
    });
    Route::get('/', 'all');
    Route::get('/detail/{id}', 'details');
});

// HospitalDepartment
Route::prefix('hospital-department')->controller(HospitalDepartmentController::class)->group(function () {
    Route::middleware(['auth:user_api', 'role:hospital'])->group(function () {
        Route::post('/add', 'add');
        Route::post('update/{id}', 'edit');
        Route::delete('/{id}', 'delete');
    });
    Route::get('/hospital/{id}', 'departmentOfHospital');
    Route::get('/detail/{id}', 'details');
});

// HospitalService
Route::prefix('hospital-service')->controller(HospitalServiceController::class)->group(function () {
    Route::middleware(['auth:user_api', 'role:hospital'])->group(function () {
        Route::post('/add', 'add');
        Route::post('update/{id}', 'edit');
        Route::delete('/{id}', 'delete');
    });
    Route::get('/hospital/{id}', 'serviceOfHospital');
    Route::get('/detail/{id}', 'details');
});

// HealthInsurance
Route::prefix('health-insurace')->controller(HealthInsuranceController::class)->group(function () {
    Route::middleware('auth:admin_api')->group(function () {
        Route::post('/add', 'add');
        Route::post('update/{id}', 'edit');
        Route::delete('/{id}', 'delete');
    });
    Route::get('/', 'all');
    Route::get('/detail/{id}', 'details');
});

// Seeder Value
Route::get('province', [ProvinceController::class, 'all']);
