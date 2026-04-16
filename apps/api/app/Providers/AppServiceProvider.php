<?php

namespace App\Providers;

use App\Models\AcademicClass;
use App\Models\AcademicSection;
use App\Policies\AcademicClassPolicy;
use App\Policies\AcademicSectionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(AcademicClass::class, AcademicClassPolicy::class);
        Gate::policy(AcademicSection::class, AcademicSectionPolicy::class);
    }
}
