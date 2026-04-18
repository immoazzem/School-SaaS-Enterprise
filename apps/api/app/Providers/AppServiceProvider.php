<?php

namespace App\Providers;

use App\Models\AcademicClass;
use App\Models\AcademicSection;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Guardian;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentAttendanceRecord;
use App\Models\StudentEnrollment;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Policies\AcademicClassPolicy;
use App\Policies\AcademicSectionPolicy;
use App\Policies\AcademicYearPolicy;
use App\Policies\ClassSubjectPolicy;
use App\Policies\DesignationPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\GuardianPolicy;
use App\Policies\ShiftPolicy;
use App\Policies\StudentAttendanceRecordPolicy;
use App\Policies\StudentEnrollmentPolicy;
use App\Policies\StudentGroupPolicy;
use App\Policies\StudentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TeacherProfilePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(10)->by(
            $request->ip().'|'.$request->input('email', '')
        ));

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(120)->by(
            optional($request->user())->id ?: $request->ip()
        ));

        Gate::policy(AcademicClass::class, AcademicClassPolicy::class);
        Gate::policy(AcademicSection::class, AcademicSectionPolicy::class);
        Gate::policy(AcademicYear::class, AcademicYearPolicy::class);
        Gate::policy(ClassSubject::class, ClassSubjectPolicy::class);
        Gate::policy(Designation::class, DesignationPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Guardian::class, GuardianPolicy::class);
        Gate::policy(Shift::class, ShiftPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(StudentAttendanceRecord::class, StudentAttendanceRecordPolicy::class);
        Gate::policy(StudentEnrollment::class, StudentEnrollmentPolicy::class);
        Gate::policy(StudentGroup::class, StudentGroupPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(TeacherProfile::class, TeacherProfilePolicy::class);
    }
}
