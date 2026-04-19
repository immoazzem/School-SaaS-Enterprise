<?php

use App\Http\Controllers\Api\AcademicClassController;
use App\Http\Controllers\Api\AcademicSectionController;
use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\Admin\AuditLogAdminController;
use App\Http\Controllers\Api\Admin\JobStatusController;
use App\Http\Controllers\Api\Admin\SchoolAdminController;
use App\Http\Controllers\Api\Admin\SystemController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarEventController;
use App\Http\Controllers\Api\ClassSubjectController;
use App\Http\Controllers\Api\DashboardSummaryController;
use App\Http\Controllers\Api\DataExportController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\DiscountPolicyController;
use App\Http\Controllers\Api\EmployeeAttendanceRecordController;
use App\Http\Controllers\Api\EmployeeAttendanceSummaryController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExamPublicationController;
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
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\ResultSummaryController;
use App\Http\Controllers\Api\SalaryRecordController;
use App\Http\Controllers\Api\SchoolAuditLogController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\SchoolDocumentController;
use App\Http\Controllers\Api\SchoolInvitationController;
use App\Http\Controllers\Api\SchoolSettingsController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StudentAnonymizationController;
use App\Http\Controllers\Api\StudentAttendanceRecordController;
use App\Http\Controllers\Api\StudentAttendanceSummaryController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentDiscountController;
use App\Http\Controllers\Api\StudentEnrollmentController;
use App\Http\Controllers\Api\StudentGroupController;
use App\Http\Controllers\Api\StudentInvoiceController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherProfileController;
use App\Http\Controllers\Api\TimetablePeriodController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/invitations/{token}/accept', [SchoolInvitationController::class, 'accept']);
        Route::middleware('super.admin')->prefix('admin')->group(function (): void {
            Route::get('schools', [SchoolAdminController::class, 'index']);
            Route::get('schools/{school}', [SchoolAdminController::class, 'show']);
            Route::patch('schools/{school}', [SchoolAdminController::class, 'update']);
            Route::delete('schools/{school}', [SchoolAdminController::class, 'destroy']);
            Route::post('schools/{school}/onboard', [SchoolAdminController::class, 'onboard']);
            Route::get('audit-logs', AuditLogAdminController::class);
            Route::get('jobs/status', [JobStatusController::class, 'index']);
            Route::post('jobs/{id}/retry', [JobStatusController::class, 'retry']);
            Route::get('users', UserAdminController::class);
            Route::get('system/health', [SystemController::class, 'health']);
            Route::get('system/stats', [SystemController::class, 'stats']);
        });
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
        Route::post('schools/{school}/calendar-events/bulk-import-holidays', [CalendarEventController::class, 'bulkImportHolidays'])
            ->middleware('school.member');
        Route::apiResource('schools.calendar-events', CalendarEventController::class)
            ->parameters(['calendar-events' => 'calendarEvent'])
            ->middleware('school.member');
        Route::apiResource('schools.class-subjects', ClassSubjectController::class)
            ->parameters(['class-subjects' => 'classSubject'])
            ->middleware('school.member');
        Route::get('schools/{school}/dashboard/summary', DashboardSummaryController::class)
            ->middleware('school.member');
        Route::get('schools/{school}/settings', [SchoolSettingsController::class, 'show'])
            ->middleware('school.member');
        Route::patch('schools/{school}/settings', [SchoolSettingsController::class, 'update'])
            ->middleware('school.member');
        Route::get('schools/{school}/audit-logs', SchoolAuditLogController::class)
            ->middleware('school.member');
        Route::apiResource('schools.invitations', SchoolInvitationController::class)
            ->only(['index', 'store', 'destroy'])
            ->middleware('school.member');
        Route::apiResource('schools.designations', DesignationController::class)
            ->middleware('school.member');
        Route::post('schools/{school}/data-export/request', [DataExportController::class, 'request'])
            ->middleware('school.member');
        Route::get('schools/{school}/data-export/{jobId}/download', [DataExportController::class, 'download'])
            ->middleware('school.member');
        Route::get('schools/{school}/documents/{document}/download', [SchoolDocumentController::class, 'download'])
            ->middleware(['school.member', 'signed'])
            ->name('schools.documents.download');
        Route::apiResource('schools.documents', SchoolDocumentController::class)
            ->only(['index', 'store', 'show', 'destroy'])
            ->parameters(['documents' => 'document'])
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
        Route::post('schools/{school}/exams/{exam}/publish', ExamPublicationController::class)
            ->middleware('school.member');
        Route::get('schools/{school}/exams/{exam}/result-summaries', [ResultSummaryController::class, 'index'])
            ->middleware('school.member');
        Route::get('schools/{school}/exams/{exam}/results', [ResultSummaryController::class, 'results'])
            ->middleware('school.member');
        Route::get('schools/{school}/exams/{exam}/results/{enrollment}', [ResultSummaryController::class, 'result'])
            ->middleware('school.member');
        Route::get('schools/{school}/exams/{exam}/marksheets', [ResultSummaryController::class, 'marksheets'])
            ->middleware('school.member');
        Route::apiResource('schools.discount-policies', DiscountPolicyController::class)
            ->parameters(['discount-policies' => 'discountPolicy'])
            ->middleware('school.member');
        Route::apiResource('schools.employee-attendance-records', EmployeeAttendanceRecordController::class)
            ->parameters(['employee-attendance-records' => 'employeeAttendanceRecord'])
            ->middleware('school.member');
        Route::get('schools/{school}/attendance/employee-summary', EmployeeAttendanceSummaryController::class)
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
        Route::get('schools/{school}/portal/student/profile', [PortalController::class, 'studentProfile'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/student/attendance', [PortalController::class, 'studentAttendance'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/student/results', [PortalController::class, 'studentResults'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/student/invoices', [PortalController::class, 'studentInvoices'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/student/notifications', [PortalController::class, 'studentNotifications'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/parent/children', [PortalController::class, 'parentChildren'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/parent/children/{enrollment}/attendance', [PortalController::class, 'parentChildAttendance'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/parent/children/{enrollment}/results', [PortalController::class, 'parentChildResults'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/parent/children/{enrollment}/invoices', [PortalController::class, 'parentChildInvoices'])
            ->middleware('school.member');
        Route::get('schools/{school}/portal/parent/notifications', [PortalController::class, 'parentNotifications'])
            ->middleware('school.member');
        Route::post('schools/{school}/promotions/preview', [PromotionController::class, 'preview'])
            ->middleware('school.member');
        Route::post('schools/{school}/promotions', [PromotionController::class, 'store'])
            ->middleware('school.member');
        Route::patch('schools/{school}/promotions/{batch}/records/{record}', [PromotionController::class, 'updateRecord'])
            ->middleware('school.member');
        Route::post('schools/{school}/promotions/{batch}/execute', [PromotionController::class, 'execute'])
            ->middleware('school.member');
        Route::post('schools/{school}/promotions/{batch}/rollback', [PromotionController::class, 'rollback'])
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
        Route::get('schools/{school}/notifications', [NotificationController::class, 'index'])
            ->middleware('school.member');
        Route::get('schools/{school}/notifications/unread-count', [NotificationController::class, 'unreadCount'])
            ->middleware('school.member');
        Route::post('schools/{school}/notifications/mark-read', [NotificationController::class, 'markRead'])
            ->middleware('school.member');
        Route::post('schools/{school}/reports/marksheet', [ReportExportController::class, 'marksheet'])
            ->middleware('school.member');
        Route::post('schools/{school}/reports/result-sheet', [ReportExportController::class, 'resultSheet'])
            ->middleware('school.member');
        Route::post('schools/{school}/reports/id-card', [ReportExportController::class, 'idCard'])
            ->middleware('school.member');
        Route::post('schools/{school}/reports/invoice/{invoice}', [ReportExportController::class, 'invoice'])
            ->middleware('school.member');
        Route::post('schools/{school}/reports/salary/{record}', [ReportExportController::class, 'salary'])
            ->middleware('school.member');
        Route::get('schools/{school}/reports/{jobId}/download', [ReportExportController::class, 'download'])
            ->middleware('school.member');
        Route::get('schools/{school}/reports/{export}/file', [ReportExportController::class, 'file'])
            ->middleware(['school.member', 'signed'])
            ->name('schools.reports.file');
        Route::apiResource('schools.salary-records', SalaryRecordController::class)
            ->parameters(['salary-records' => 'salaryRecord'])
            ->middleware('school.member');
        Route::apiResource('schools.shifts', ShiftController::class)
            ->middleware('school.member');
        Route::apiResource('schools.timetable-periods', TimetablePeriodController::class)
            ->parameters(['timetable-periods' => 'timetablePeriod'])
            ->middleware('school.member');
        Route::apiResource('schools.students', StudentController::class)
            ->middleware('school.member');
        Route::post('schools/{school}/students/{student}/anonymize', StudentAnonymizationController::class)
            ->middleware('school.member');
        Route::post('schools/{school}/student-attendance/bulk', [StudentAttendanceRecordController::class, 'bulk'])
            ->middleware('school.member');
        Route::get('schools/{school}/attendance/summary', StudentAttendanceSummaryController::class)
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
});
