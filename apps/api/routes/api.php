<?php

use App\Http\Controllers\Api\AcademicClassController;
use App\Http\Controllers\Api\AcademicSectionController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassSubjectController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\DiscountPolicyController;
use App\Http\Controllers\Api\EmployeeAttendanceRecordController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExamScheduleController;
use App\Http\Controllers\Api\ExamTypeController;
use App\Http\Controllers\Api\FeeCategoryController;
use App\Http\Controllers\Api\FeeStructureController;
use App\Http\Controllers\Api\GradeScaleController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\InvoicePaymentController;
use App\Http\Controllers\Api\LeaveApplicationController;
use App\Http\Controllers\Api\LeaveBalanceController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\MarksEntryController;
use App\Http\Controllers\Api\SalaryRecordController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StudentAttendanceRecordController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentDiscountController;
use App\Http\Controllers\Api\StudentEnrollmentController;
use App\Http\Controllers\Api\StudentGroupController;
use App\Http\Controllers\Api\StudentInvoiceController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::apiResource('schools', SchoolController::class)->only(['index', 'store']);
    Route::apiResource('schools', SchoolController::class)
        ->only(['show', 'update'])
        ->middleware('school.member');
    Route::apiResource('schools.academic-classes', AcademicClassController::class)
        ->middleware('school.member');
    Route::apiResource('schools.academic-years', AcademicYearController::class)
        ->middleware('school.member');
    Route::apiResource('schools.academic-sections', AcademicSectionController::class)
        ->middleware('school.member');
    Route::apiResource('schools.class-subjects', ClassSubjectController::class)
        ->parameters(['class-subjects' => 'classSubject'])
        ->middleware('school.member');
    Route::apiResource('schools.designations', DesignationController::class)
        ->middleware('school.member');
    Route::apiResource('schools.employees', EmployeeController::class)
        ->middleware('school.member');
    Route::apiResource('schools.exam-schedules', ExamScheduleController::class)
        ->parameters(['exam-schedules' => 'examSchedule'])
        ->middleware('school.member');
    Route::apiResource('schools.exam-types', ExamTypeController::class)
        ->parameters(['exam-types' => 'examType'])
        ->middleware('school.member');
    Route::apiResource('schools.exams', ExamController::class)
        ->middleware('school.member');
    Route::apiResource('schools.discount-policies', DiscountPolicyController::class)
        ->parameters(['discount-policies' => 'discountPolicy'])
        ->middleware('school.member');
    Route::apiResource('schools.employee-attendance-records', EmployeeAttendanceRecordController::class)
        ->parameters(['employee-attendance-records' => 'employeeAttendanceRecord'])
        ->middleware('school.member');
    Route::apiResource('schools.fee-categories', FeeCategoryController::class)
        ->parameters(['fee-categories' => 'feeCategory'])
        ->middleware('school.member');
    Route::apiResource('schools.fee-structures', FeeStructureController::class)
        ->parameters(['fee-structures' => 'feeStructure'])
        ->middleware('school.member');
    Route::apiResource('schools.grade-scales', GradeScaleController::class)
        ->parameters(['grade-scales' => 'gradeScale'])
        ->middleware('school.member');
    Route::apiResource('schools.guardians', GuardianController::class)
        ->middleware('school.member');
    Route::apiResource('schools.invoice-payments', InvoicePaymentController::class)
        ->parameters(['invoice-payments' => 'invoicePayment'])
        ->middleware('school.member');
    Route::patch('schools/{school}/leave-applications/{leaveApplication}/approve', [LeaveApplicationController::class, 'approve'])
        ->middleware('school.member');
    Route::patch('schools/{school}/leave-applications/{leaveApplication}/reject', [LeaveApplicationController::class, 'reject'])
        ->middleware('school.member');
    Route::patch('schools/{school}/leave-applications/{leaveApplication}/cancel', [LeaveApplicationController::class, 'cancel'])
        ->middleware('school.member');
    Route::apiResource('schools.leave-applications', LeaveApplicationController::class)
        ->parameters(['leave-applications' => 'leaveApplication'])
        ->middleware('school.member');
    Route::apiResource('schools.leave-balances', LeaveBalanceController::class)
        ->parameters(['leave-balances' => 'leaveBalance'])
        ->middleware('school.member');
    Route::apiResource('schools.leave-types', LeaveTypeController::class)
        ->parameters(['leave-types' => 'leaveType'])
        ->middleware('school.member');
    Route::post('schools/{school}/marks-entries/bulk', [MarksEntryController::class, 'bulk'])
        ->middleware('school.member');
    Route::patch('schools/{school}/marks-entries/{marksEntry}/verify', [MarksEntryController::class, 'verify'])
        ->middleware('school.member');
    Route::apiResource('schools.marks-entries', MarksEntryController::class)
        ->parameters(['marks-entries' => 'marksEntry'])
        ->middleware('school.member');
    Route::apiResource('schools.salary-records', SalaryRecordController::class)
        ->parameters(['salary-records' => 'salaryRecord'])
        ->middleware('school.member');
    Route::apiResource('schools.shifts', ShiftController::class)
        ->middleware('school.member');
    Route::apiResource('schools.students', StudentController::class)
        ->middleware('school.member');
    Route::post('schools/{school}/student-attendance/bulk', [StudentAttendanceRecordController::class, 'bulk'])
        ->middleware('school.member');
    Route::apiResource('schools.student-attendance-records', StudentAttendanceRecordController::class)
        ->parameters(['student-attendance-records' => 'studentAttendanceRecord'])
        ->middleware('school.member');
    Route::apiResource('schools.student-discounts', StudentDiscountController::class)
        ->parameters(['student-discounts' => 'studentDiscount'])
        ->middleware('school.member');
    Route::apiResource('schools.student-enrollments', StudentEnrollmentController::class)
        ->parameters(['student-enrollments' => 'studentEnrollment'])
        ->middleware('school.member');
    Route::apiResource('schools.student-groups', StudentGroupController::class)
        ->parameters(['student-groups' => 'studentGroup'])
        ->middleware('school.member');
    Route::post('schools/{school}/student-invoices/bulk-generate', [StudentInvoiceController::class, 'bulkGenerate'])
        ->middleware('school.member');
    Route::apiResource('schools.student-invoices', StudentInvoiceController::class)
        ->parameters(['student-invoices' => 'studentInvoice'])
        ->middleware('school.member');
    Route::apiResource('schools.subjects', SubjectController::class)
        ->middleware('school.member');
    Route::apiResource('schools.teacher-profiles', TeacherProfileController::class)
        ->parameters(['teacher-profiles' => 'teacherProfile'])
        ->middleware('school.member');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
