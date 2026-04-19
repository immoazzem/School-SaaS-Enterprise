<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'plan',
        'subscription_status',
        'trial_ends_at',
        'plan_limits',
        'locale',
        'timezone',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'plan_limits' => 'array',
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (School $school): void {
            $school->public_id ??= (string) Str::ulid();
            $school->slug = Str::slug($school->slug ?: $school->name);
        });
    }

    /**
     * @return HasMany<SchoolMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_memberships')
            ->withPivot(['status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<SchoolInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(SchoolInvitation::class);
    }

    /**
     * @return HasMany<DataExportJob, $this>
     */
    public function dataExportJobs(): HasMany
    {
        return $this->hasMany(DataExportJob::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * @return HasMany<PromotionBatch, $this>
     */
    public function promotionBatches(): HasMany
    {
        return $this->hasMany(PromotionBatch::class);
    }

    /**
     * @return HasMany<AcademicClass, $this>
     */
    public function academicClasses(): HasMany
    {
        return $this->hasMany(AcademicClass::class);
    }

    /**
     * @return HasMany<AcademicYear, $this>
     */
    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    /**
     * @return HasMany<AcademicSection, $this>
     */
    public function academicSections(): HasMany
    {
        return $this->hasMany(AcademicSection::class);
    }

    /**
     * @return HasMany<Subject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * @return HasMany<StudentGroup, $this>
     */
    public function studentGroups(): HasMany
    {
        return $this->hasMany(StudentGroup::class);
    }

    /**
     * @return HasMany<Shift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * @return HasMany<TimetablePeriod, $this>
     */
    public function timetablePeriods(): HasMany
    {
        return $this->hasMany(TimetablePeriod::class);
    }

    /**
     * @return HasMany<ClassSubject, $this>
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    /**
     * @return HasMany<Designation, $this>
     */
    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return HasMany<Guardian, $this>
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    /**
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * @return HasMany<StudentEnrollment, $this>
     */
    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * @return HasMany<StudentAttendanceRecord, $this>
     */
    public function studentAttendanceRecords(): HasMany
    {
        return $this->hasMany(StudentAttendanceRecord::class);
    }

    /**
     * @return HasMany<ExamType, $this>
     */
    public function examTypes(): HasMany
    {
        return $this->hasMany(ExamType::class);
    }

    /**
     * @return HasMany<Exam, $this>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * @return HasMany<ExamSchedule, $this>
     */
    public function examSchedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }

    /**
     * @return HasMany<TeacherProfile, $this>
     */
    public function teacherProfiles(): HasMany
    {
        return $this->hasMany(TeacherProfile::class);
    }

    /**
     * @return HasMany<GradeScale, $this>
     */
    public function gradeScales(): HasMany
    {
        return $this->hasMany(GradeScale::class);
    }

    /**
     * @return HasMany<MarksEntry, $this>
     */
    public function marksEntries(): HasMany
    {
        return $this->hasMany(MarksEntry::class);
    }

    /**
     * @return HasMany<FeeCategory, $this>
     */
    public function feeCategories(): HasMany
    {
        return $this->hasMany(FeeCategory::class);
    }

    /**
     * @return HasMany<FeeStructure, $this>
     */
    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    /**
     * @return HasMany<DiscountPolicy, $this>
     */
    public function discountPolicies(): HasMany
    {
        return $this->hasMany(DiscountPolicy::class);
    }

    /**
     * @return HasMany<StudentDiscount, $this>
     */
    public function studentDiscounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    /**
     * @return HasMany<StudentInvoice, $this>
     */
    public function studentInvoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }

    /**
     * @return HasMany<InvoicePayment, $this>
     */
    public function invoicePayments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /**
     * @return HasMany<SalaryRecord, $this>
     */
    public function salaryRecords(): HasMany
    {
        return $this->hasMany(SalaryRecord::class);
    }

    /**
     * @return HasMany<EmployeeAttendanceRecord, $this>
     */
    public function employeeAttendanceRecords(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceRecord::class);
    }

    /**
     * @return HasMany<LeaveType, $this>
     */
    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }

    /**
     * @return HasMany<LeaveBalance, $this>
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * @return HasMany<LeaveApplication, $this>
     */
    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    /**
     * @return HasMany<CalendarEvent, $this>
     */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * @return HasMany<SchoolDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SchoolDocument::class);
    }

    /**
     * @return HasMany<ReportExport, $this>
     */
    public function reportExports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }

    /**
     * @return HasMany<ResultSummary, $this>
     */
    public function resultSummaries(): HasMany
    {
        return $this->hasMany(ResultSummary::class);
    }

    /**
     * @return HasMany<SchoolNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(SchoolNotification::class);
    }

    /**
     * @return HasMany<NotificationTemplate, $this>
     */
    public function notificationTemplates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    /**
     * @return HasMany<SmsLog, $this>
     */
    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }
}
