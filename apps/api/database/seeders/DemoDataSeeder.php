<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSection;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CalendarEvent;
use App\Models\ClassSubject;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAttendanceRecord;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamType;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\GradeScale;
use App\Models\InvoicePayment;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\MarksEntry;
use App\Models\PaymentGatewayConfig;
use App\Models\ResultSummary;
use App\Models\Role;
use App\Models\SalaryRecord;
use App\Models\School;
use App\Models\SchoolDocument;
use App\Models\SchoolMembership;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentAttendanceRecord;
use App\Models\StudentEnrollment;
use App\Models\StudentGroup;
use App\Models\StudentInvoice;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TimetablePeriod;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(EnterpriseRolePermissionSeeder::class);

        DB::transaction(function (): void {
            $user = User::query()->firstOrCreate(
                ['email' => 'test@example.com'],
                ['name' => 'Test User', 'password' => bcrypt('password')]
            );

            $school = School::query()->firstOrCreate(
                ['slug' => 'phase-four-browser-school'],
                [
                    'name' => 'Phase Four Browser School',
                    'status' => 'active',
                    'timezone' => 'Asia/Dhaka',
                    'locale' => 'en',
                    'plan' => 'enterprise',
                    'subscription_status' => 'trialing',
                    'trial_ends_at' => now()->addDays(30),
                    'plan_limits' => ['students' => 5000, 'employees' => 500, 'documents' => 10000],
                ]
            );

            SchoolMembership::query()->updateOrCreate(
                ['school_id' => $school->id, 'user_id' => $user->id],
                ['status' => 'active', 'joined_at' => now()]
            );

            $ownerRole = Role::query()->where('key', 'school-owner')->first();
            if ($ownerRole) {
                UserRoleAssignment::query()->firstOrCreate([
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'role_id' => $ownerRole->id,
                ], [
                    'assigned_by' => $user->id,
                ]);
            }

            $year = AcademicYear::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'AY-2026'],
                [
                    'name' => 'Academic Year 2026',
                    'starts_on' => '2026-01-01',
                    'ends_on' => '2026-12-31',
                    'is_current' => true,
                    'status' => 'active',
                ]
            );

            $class = AcademicClass::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'CLS-ONE'],
                ['name' => 'Class One', 'description' => 'Primary demo class', 'sort_order' => 1, 'status' => 'active']
            );

            $section = AcademicSection::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'A'],
                [
                    'academic_class_id' => $class->id,
                    'name' => 'Section A',
                    'capacity' => 40,
                    'room' => '101',
                    'sort_order' => 1,
                    'status' => 'active',
                ]
            );

            $group = StudentGroup::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'SCI'],
                ['name' => 'Science Group', 'description' => 'Science demo track', 'sort_order' => 1, 'status' => 'active']
            );

            $shift = Shift::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'MOR'],
                [
                    'name' => 'Morning Shift',
                    'starts_at' => '08:00',
                    'ends_at' => '12:00',
                    'description' => 'Morning academic operations',
                    'sort_order' => 1,
                    'status' => 'active',
                ]
            );

            $subject = Subject::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'MATH-101'],
                [
                    'name' => 'Mathematics',
                    'type' => 'core',
                    'description' => 'Primary mathematics',
                    'credit_hours' => 4,
                    'sort_order' => 1,
                    'status' => 'active',
                ]
            );

            $classSubject = ClassSubject::query()->updateOrCreate(
                ['school_id' => $school->id, 'academic_class_id' => $class->id, 'subject_id' => $subject->id],
                [
                    'full_marks' => 100,
                    'pass_marks' => 40,
                    'subjective_marks' => 60,
                    'sort_order' => 1,
                    'status' => 'active',
                ]
            );

            $designation = Designation::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'TCHR'],
                ['name' => 'Teacher', 'description' => 'Teaching staff', 'sort_order' => 1, 'status' => 'active']
            );

            $employee = Employee::query()->updateOrCreate(
                ['school_id' => $school->id, 'employee_no' => 'EMP-DEMO-001'],
                [
                    'designation_id' => $designation->id,
                    'full_name' => 'Amina Rahman',
                    'name_bn' => 'আমিনা রহমান',
                    'email' => 'amina.teacher@example.com',
                    'phone' => '+8801700000001',
                    'gender' => 'female',
                    'religion' => 'Islam',
                    'date_of_birth' => '1990-02-14',
                    'joined_on' => '2026-01-01',
                    'salary' => 45000,
                    'employee_type' => 'teacher',
                    'address' => 'Dhaka',
                    'notes' => 'Demo teacher profile',
                    'status' => 'active',
                ]
            );

            TeacherProfile::query()->updateOrCreate(
                ['school_id' => $school->id, 'employee_id' => $employee->id],
                [
                    'teacher_no' => 'TCHR-DEMO-001',
                    'specialization' => 'Mathematics',
                    'qualification' => 'M.Ed',
                    'experience_years' => 8,
                    'joined_teaching_on' => '2026-01-01',
                    'bio' => 'Demo teacher for browser checks.',
                    'status' => 'active',
                ]
            );

            $student = Student::query()->updateOrCreate(
                ['school_id' => $school->id, 'admission_no' => 'ADM-ASSIGN-001'],
                [
                    'full_name' => 'Assignment Demo Student',
                    'name_bn' => 'ডেমো শিক্ষার্থী',
                    'father_name' => 'Demo Father',
                    'mother_name' => 'Demo Mother',
                    'gender' => 'female',
                    'date_of_birth' => '2018-03-10',
                    'admitted_on' => '2026-01-01',
                    'address' => 'Dhaka',
                    'medical_notes' => 'No known issues',
                    'status' => 'active',
                ]
            );

            $enrollment = StudentEnrollment::query()->updateOrCreate(
                ['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id],
                [
                    'academic_class_id' => $class->id,
                    'academic_section_id' => $section->id,
                    'student_group_id' => $group->id,
                    'shift_id' => $shift->id,
                    'roll_no' => '21',
                    'enrolled_on' => '2026-01-01',
                    'status' => 'active',
                    'notes' => 'Demo enrollment',
                ]
            );

            StudentAttendanceRecord::query()->updateOrCreate(
                ['school_id' => $school->id, 'student_enrollment_id' => $enrollment->id, 'attendance_date' => '2026-04-20'],
                ['status' => 'present', 'remarks' => 'Demo present record']
            );

            TimetablePeriod::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'academic_class_id' => $class->id,
                    'shift_id' => $shift->id,
                    'day_of_week' => 0,
                    'period_number' => 1,
                ],
                [
                    'start_time' => '08:00',
                    'end_time' => '08:45',
                    'subject_id' => $subject->id,
                    'teacher_user_id' => $user->id,
                    'room' => '204',
                    'status' => 'active',
                ]
            );

            $assignment = Assignment::query()->updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Algebra practice browser check'],
                [
                    'academic_class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'assigned_by' => $user->id,
                    'description' => 'Solve ten algebra questions.',
                    'due_date' => '2026-04-30',
                    'attachment_path' => null,
                    'is_published' => true,
                    'status' => 'active',
                ]
            );

            AssignmentSubmission::query()->updateOrCreate(
                ['school_id' => $school->id, 'assignment_id' => $assignment->id, 'student_enrollment_id' => $enrollment->id],
                [
                    'submitted_at' => now(),
                    'marks_awarded' => 87,
                    'feedback' => 'Strong demo submission.',
                    'status' => 'graded',
                ]
            );

            $examType = ExamType::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'MID'],
                [
                    'name' => 'Mid Term',
                    'weightage_percent' => 40,
                    'description' => 'Mid-year assessment',
                    'sort_order' => 1,
                    'status' => 'active',
                ]
            );

            $exam = Exam::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'MID-2026'],
                [
                    'exam_type_id' => $examType->id,
                    'academic_year_id' => $year->id,
                    'name' => 'Mid Term 2026',
                    'starts_on' => '2026-06-01',
                    'ends_on' => '2026-06-10',
                    'is_published' => true,
                    'published_at' => now(),
                    'published_by' => $user->id,
                    'status' => 'scheduled',
                    'notes' => 'Demo published exam',
                ]
            );

            ExamSchedule::query()->updateOrCreate(
                ['school_id' => $school->id, 'exam_id' => $exam->id, 'class_subject_id' => $classSubject->id],
                [
                    'exam_date' => '2026-06-03',
                    'starts_at' => '09:00',
                    'ends_at' => '11:00',
                    'room' => 'Exam Hall 1',
                    'instructions' => 'Bring pencils and admit card.',
                    'status' => 'scheduled',
                ]
            );

            $marks = MarksEntry::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'exam_id' => $exam->id,
                    'class_subject_id' => $classSubject->id,
                    'student_enrollment_id' => $enrollment->id,
                ],
                [
                    'marks_obtained' => 88,
                    'full_marks' => 100,
                    'pass_marks' => 40,
                    'is_absent' => false,
                    'verification_status' => 'verified',
                    'entered_by' => $user->id,
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'remarks' => 'Demo marks entry',
                ]
            );

            GradeScale::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'A+'],
                [
                    'name' => 'Excellent',
                    'min_percent' => 80,
                    'max_percent' => 100,
                    'grade_point' => 5,
                    'fail_below_percent' => 40,
                    'gpa_calculation_method' => 'weighted',
                    'status' => 'active',
                ]
            );

            ResultSummary::query()->updateOrCreate(
                ['exam_id' => $exam->id, 'student_enrollment_id' => $enrollment->id],
                [
                    'school_id' => $school->id,
                    'total_marks_obtained' => $marks->marks_obtained,
                    'total_full_marks' => 100,
                    'percentage' => 88,
                    'gpa' => 5,
                    'grade' => 'A+',
                    'position_in_class' => 1,
                    'is_pass' => true,
                    'computed_at' => now(),
                ]
            );

            $feeCategory = FeeCategory::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'TUITION'],
                ['name' => 'Tuition Fee', 'description' => 'Monthly tuition', 'sort_order' => 1, 'billing_type' => 'monthly', 'status' => 'active']
            );

            FeeStructure::query()->updateOrCreate(
                ['school_id' => $school->id, 'fee_category_id' => $feeCategory->id, 'academic_year_id' => $year->id, 'academic_class_id' => $class->id],
                [
                    'student_group_id' => $group->id,
                    'amount' => 2500,
                    'due_day_of_month' => 10,
                    'months_applicable' => ['2026-04', '2026-05', '2026-06'],
                    'is_recurring' => true,
                    'status' => 'active',
                ]
            );

            $invoice = StudentInvoice::query()->updateOrCreate(
                ['school_id' => $school->id, 'invoice_no' => 'INV-DEMO-2026-04'],
                [
                    'student_enrollment_id' => $enrollment->id,
                    'academic_year_id' => $year->id,
                    'fee_month' => '2026-04',
                    'subtotal' => 2500,
                    'discount' => 0,
                    'discount_breakdown' => [],
                    'total' => 2500,
                    'paid_amount' => 2500,
                    'due_date' => '2026-04-10',
                    'status' => 'paid',
                    'notes' => 'Demo invoice paid through seed data.',
                ]
            );

            InvoicePayment::query()->updateOrCreate(
                ['school_id' => $school->id, 'student_invoice_id' => $invoice->id, 'transaction_ref' => 'PAY-DEMO-2026-04'],
                [
                    'amount' => 2500,
                    'paid_on' => '2026-04-08',
                    'payment_method' => 'bkash',
                    'payment_channel_metadata' => ['gateway' => 'bkash', 'mode' => 'test'],
                    'notes' => 'Demo payment',
                ]
            );

            PaymentGatewayConfig::query()->updateOrCreate(
                ['school_id' => $school->id, 'gateway' => 'bkash'],
                [
                    'credentials_encrypted' => [
                        'merchant_id' => 'demo-merchant',
                        'public_key' => 'demo-public',
                        'secret_key' => 'demo-secret',
                    ],
                    'is_active' => true,
                    'test_mode' => true,
                ]
            );

            SalaryRecord::query()->updateOrCreate(
                ['school_id' => $school->id, 'employee_id' => $employee->id, 'month' => '2026-04'],
                [
                    'academic_year_id' => $year->id,
                    'basic_amount' => 45000,
                    'allowances' => ['house_rent' => 5000, 'medical' => 1500],
                    'gross_amount' => 51500,
                    'deductions' => ['provident_fund' => 2000, 'income_tax' => 1000],
                    'total_deductions' => 3000,
                    'net_amount' => 48500,
                    'paid_at' => now(),
                    'payment_method' => 'bank',
                    'transaction_ref' => 'SAL-DEMO-2026-04',
                    'notes' => 'Demo paid salary',
                    'status' => 'paid',
                ]
            );

            EmployeeAttendanceRecord::query()->updateOrCreate(
                ['school_id' => $school->id, 'employee_id' => $employee->id, 'date' => '2026-04-20'],
                [
                    'status' => 'present',
                    'check_in_time' => '07:45',
                    'check_out_time' => '13:00',
                    'notes' => 'Demo staff attendance',
                    'recorded_by' => $user->id,
                ]
            );

            $leaveType = LeaveType::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => 'CL'],
                ['name' => 'Casual Leave', 'max_days_per_year' => 10, 'is_paid' => true, 'requires_approval' => true, 'status' => 'active']
            );

            LeaveBalance::query()->updateOrCreate(
                ['school_id' => $school->id, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'academic_year_id' => $year->id],
                ['total_days' => 10, 'used_days' => 1, 'remaining_days' => 9]
            );

            LeaveApplication::query()->updateOrCreate(
                ['school_id' => $school->id, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'from_date' => '2026-04-25'],
                [
                    'to_date' => '2026-04-25',
                    'total_days' => 1,
                    'reason' => 'Demo leave request',
                    'status' => 'approved',
                    'applied_at' => now()->subDay(),
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'review_note' => 'Approved for demo data.',
                ]
            );

            CalendarEvent::query()->updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Demo Parents Meeting', 'starts_on' => '2026-04-28'],
                [
                    'academic_year_id' => $year->id,
                    'academic_class_id' => $class->id,
                    'description' => 'Parent-teacher meeting for demo checks.',
                    'ends_on' => '2026-04-28',
                    'starts_at' => '10:00',
                    'ends_at' => '11:00',
                    'location' => 'Room 101',
                    'is_holiday' => false,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]
            );

            Storage::disk('local')->put('demo/demo-circular.txt', 'Demo circular for School SaaS Enterprise browser verification.');

            SchoolDocument::query()->updateOrCreate(
                ['school_id' => $school->id, 'title' => 'Demo Circular'],
                [
                    'uploader_id' => $user->id,
                    'category' => 'circular',
                    'file_path' => 'demo/demo-circular.txt',
                    'file_name' => 'demo-circular.txt',
                    'file_size_bytes' => strlen('Demo circular for School SaaS Enterprise browser verification.'),
                    'mime_type' => 'text/plain',
                    'is_public' => true,
                    'uploaded_at' => now(),
                ]
            );
        });
    }
}
