<?php

namespace App\Providers;

use App\Repositories\AdminInterface;
use App\Repositories\AdminRepository;
use App\Repositories\ExampleInterface;
use App\Repositories\ExampleRepository;
use App\Repositories\InforDoctorInterface;
use App\Repositories\InforDoctorRepository;
use App\Repositories\InforHospitalInterface;
use App\Repositories\InforHospitalRepository;
use App\Repositories\InforUserInterface;
use App\Repositories\InforUserRepository;
use App\Repositories\UserInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ExampleInterface::class, ExampleRepository::class);
        $this->app->bind(AdminInterface::class, AdminRepository::class);
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(InforUserInterface::class, InforUserRepository::class);
        $this->app->bind(InforHospitalInterface::class, InforHospitalRepository::class);
        $this->app->bind(InforDoctorInterface::class, InforDoctorRepository::class);
        $this->app->bind(PasswordResetInterface::class, PasswordResetRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
