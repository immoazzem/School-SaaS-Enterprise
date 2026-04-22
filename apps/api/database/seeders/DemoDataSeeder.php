<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSection;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
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
use App\Models\Guardian;
use App\Models\InvoicePayment;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\MarksEntry;
use App\Models\PaymentGatewayConfig;
use App\Models\PromotionBatch;
use App\Models\PromotionRecord;
use App\Models\ResultSummary;
use App\Models\Role;
use App\Models\SalaryRecord;
use App\Models\School;
use App\Models\SchoolDocument;
use App\Models\SchoolInvitation;
use App\Models\SchoolMembership;
use App\Models\SchoolNotification;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentAttendanceRecord;
use App\Models\StudentDiscount;
use App\Models\StudentEnrollment;
use App\Models\StudentGroup;
use App\Models\StudentInvoice;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TimetablePeriod;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * A deterministic ten-year operating dataset for local QA and browser smoke checks.
     */
    public function run(): void
    {
        $this->call(EnterpriseRolePermissionSeeder::class);

        DB::transaction(function (): void {
            $user = User::query()->updateOrCreate(
                ['email' => 'test@example.com'],
                ['name' => 'Test User', 'password' => bcrypt('password')]
            );

            $school = School::query()->updateOrCreate(
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
                    'settings' => [
                        'currency' => 'BDT',
                        'academic_year_start_month' => 1,
                        'allow_parent_portal' => true,
                        'allow_student_portal' => true,
                        'fee_invoice_prefix' => 'INV-DEMO',
                        'attendance_warning_threshold_percent' => 75,
                        'sms_enabled' => true,
                        'sms_provider' => 'log',
                        'sms_api_key' => 'demo-sms-key',
                        'pdf_footer_text' => 'Demo School SaaS Enterprise',
                    ],
                ]
            );

            SchoolMembership::query()->updateOrCreate(
                ['school_id' => $school->id, 'user_id' => $user->id],
                ['status' => 'active', 'joined_at' => CarbonImmutable::create(2022, 1, 1)]
            );

            $ownerRole = Role::query()->where('key', 'school-owner')->first();
            if ($ownerRole) {
                UserRoleAssignment::query()->updateOrCreate(
                    ['school_id' => $school->id, 'user_id' => $user->id, 'role_id' => $ownerRole->id],
                    ['assigned_by' => $user->id]
                );
            }

            $years = $this->seedAcademicYears($school);
            [$classes, $sections, $groups, $shifts, $subjects, $classSubjects] = $this->seedAcademicSetup($school);
            [$employees, $teacherUsers, $staffUsers] = $this->seedEmployees($school, $user);
            $students = $this->seedStudents($school);
            $roleUsers = $this->seedAccessUsers($school, $user, $staffUsers, $students);
            $enrollments = $this->seedEnrollments($school, $students, $years, $classes, $sections, $groups, $shifts);
            $this->seedTimetable($school, $years, $classes, $shifts, $subjects, $teacherUsers);
            $this->seedAssignments($school, $user, $years, $classes, $subjects, $enrollments);
            [$exams, $examSchedules] = $this->seedExams($school, $user, $years, $classSubjects);
            $this->seedMarksAndResults($school, $user, $exams, $examSchedules, $enrollments);
            $this->seedAttendance($school, $user, $enrollments, $employees, $years);
            $this->seedFinance($school, $user, $years, $classes, $groups, $enrollments);
            $this->seedStaffOperations($school, $user, $years, $employees);
            $this->seedPromotions($school, $user, $years, $classes, $enrollments);
            $this->seedCalendarDocumentsAndAudit($school, $user, $years, $classes);
            $this->seedInvitationsAndNotifications($school, $user, $roleUsers, $years, $enrollments);
        });
    }

    /**
     * @return array<int, AcademicYear>
     */
    private function seedAcademicYears(School $school): array
    {
        $years = [];
        for ($year = 2017; $year <= 2026; $year++) {
            $years[$year] = AcademicYear::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => "AY-{$year}"],
                [
                    'name' => "Academic Year {$year}",
                    'starts_on' => "{$year}-01-01",
                    'ends_on' => "{$year}-12-31",
                    'is_current' => $year === 2026,
                    'status' => $year < 2026 ? 'archived' : 'active',
                ]
            );
        }

        return $years;
    }

    /**
     * @return array{0: array<int, AcademicClass>, 1: array<string, AcademicSection>, 2: array<int, StudentGroup>, 3: array<int, Shift>, 4: array<int, Subject>, 5: array<string, ClassSubject>}
     */
    private function seedAcademicSetup(School $school): array
    {
        $classes = [];
        foreach ([
            1 => ['Class One', 'CLS-ONE'],
            2 => ['Class Two', 'CLS-TWO'],
            3 => ['Class Three', 'CLS-THREE'],
            4 => ['Class Four', 'CLS-FOUR'],
            5 => ['Class Five', 'CLS-FIVE'],
        ] as $sort => [$name, $code]) {
            $classes[$sort] = AcademicClass::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $name, 'description' => "Primary level {$sort}", 'sort_order' => $sort, 'status' => 'active']
            );
        }

        $sections = [];
        foreach ($classes as $index => $class) {
            foreach (['A', 'B'] as $offset => $sectionCode) {
                $code = "C{$index}-{$sectionCode}";
                $sections[$code] = AcademicSection::query()->updateOrCreate(
                    ['school_id' => $school->id, 'code' => $code],
                    [
                        'academic_class_id' => $class->id,
                        'name' => "Section {$sectionCode}",
                        'capacity' => 35,
                        'room' => "{$index}0".($offset + 1),
                        'sort_order' => $offset + 1,
                        'status' => 'active',
                    ]
                );
            }
        }

        $groups = [];
        foreach ([['Science Group', 'SCI'], ['General Group', 'GEN']] as $sort => [$name, $code]) {
            $groups[$sort + 1] = StudentGroup::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $name, 'description' => "{$name} demo track", 'sort_order' => $sort + 1, 'status' => 'active']
            );
        }

        $shifts = [];
        foreach ([['Morning Shift', 'MOR', '08:00', '12:00'], ['Day Shift', 'DAY', '12:30', '16:30']] as $sort => [$name, $code, $start, $end]) {
            $shifts[$sort + 1] = Shift::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $name, 'starts_at' => $start, 'ends_at' => $end, 'description' => "{$name} operations", 'sort_order' => $sort + 1, 'status' => 'active']
            );
        }

        $subjects = [];
        foreach ([
            ['Mathematics', 'MATH', 'core'],
            ['English', 'ENG', 'core'],
            ['Science', 'SCIENCE', 'core'],
            ['Bangla', 'BAN', 'core'],
            ['Arts', 'ART', 'co_curricular'],
        ] as $sort => [$name, $code, $type]) {
            $subjects[$sort + 1] = Subject::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'description' => "{$name} curriculum",
                    'credit_hours' => $type === 'co_curricular' ? 2 : 4,
                    'sort_order' => $sort + 1,
                    'status' => 'active',
                ]
            );
        }

        $classSubjects = [];
        foreach ($classes as $classIndex => $class) {
            foreach ($subjects as $subjectIndex => $subject) {
                $classSubjects["{$classIndex}-{$subjectIndex}"] = ClassSubject::query()->updateOrCreate(
                    ['school_id' => $school->id, 'academic_class_id' => $class->id, 'subject_id' => $subject->id],
                    [
                        'full_marks' => 100,
                        'pass_marks' => 40,
                        'subjective_marks' => $subject->type === 'co_curricular' ? 30 : 60,
                        'sort_order' => $subjectIndex,
                        'status' => 'active',
                    ]
                );
            }
        }

        return [$classes, $sections, $groups, $shifts, $subjects, $classSubjects];
    }

    /**
     * @return array{0: array<int, Employee>, 1: array<int, User>, 2: array<string, User>}
     */
    private function seedEmployees(School $school, User $owner): array
    {
        $designationMap = [];
        foreach ([['Principal', 'PRIN'], ['Teacher', 'TCHR'], ['Accountant', 'ACCT'], ['Office Assistant', 'OFFC']] as $sort => [$name, $code]) {
            $designationMap[$code] = Designation::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $name, 'description' => "{$name} role", 'sort_order' => $sort + 1, 'status' => 'active']
            );
        }

        $employees = [];
        $teacherUsers = [];
        $staffUsers = [];
        $names = [
            ['Amina Rahman', 'female', 'TCHR', 'Mathematics'],
            ['Karim Uddin', 'male', 'TCHR', 'English'],
            ['Nusrat Jahan', 'female', 'TCHR', 'Science'],
            ['Rafiq Hasan', 'male', 'TCHR', 'Bangla'],
            ['Farhana Kabir', 'female', 'PRIN', 'Administration'],
            ['Mahmud Alam', 'male', 'ACCT', 'Finance'],
            ['Sadia Islam', 'female', 'OFFC', 'Operations'],
            ['Tanvir Ahmed', 'male', 'TCHR', 'Arts'],
        ];

        foreach ($names as $index => [$name, $gender, $designationCode, $specialization]) {
            $employeeNo = 'EMP-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $userEmail = strtolower(str_replace(' ', '.', $name)).'@example.com';
            $employees[$index + 1] = Employee::query()->updateOrCreate(
                ['school_id' => $school->id, 'employee_no' => $employeeNo],
                [
                    'designation_id' => $designationMap[$designationCode]->id,
                    'full_name' => $name,
                    'email' => $userEmail,
                    'phone' => '+88017'.str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT),
                    'gender' => $gender,
                    'religion' => 'Islam',
                    'date_of_birth' => 1985 + $index.'-02-14',
                    'joined_on' => '2022-01-01',
                    'salary' => 32000 + ($index * 2500),
                    'employee_type' => $designationCode === 'TCHR' ? 'teacher' : 'full_time',
                    'address' => 'Dhaka',
                    'notes' => 'Five-year demo staff profile',
                    'status' => 'active',
                ]
            );

            $staffUser = User::query()->updateOrCreate(
                ['email' => $userEmail],
                ['name' => $name, 'password' => bcrypt('password')]
            );
            SchoolMembership::query()->updateOrCreate(
                ['school_id' => $school->id, 'user_id' => $staffUser->id],
                ['status' => 'active', 'joined_at' => CarbonImmutable::create(2017, 1, 1)]
            );
            $staffUsers[$designationCode === 'TCHR' ? strtolower(str_replace(' ', '-', $name)) : strtolower($designationCode)] = $staffUser;

            $roleKey = match ($designationCode) {
                'TCHR' => 'teacher',
                'PRIN' => 'principal',
                'ACCT' => 'accountant',
                default => 'school-admin',
            };
            $role = Role::query()->where('key', $roleKey)->first();
            if ($role) {
                UserRoleAssignment::query()->updateOrCreate(
                    ['school_id' => $school->id, 'user_id' => $staffUser->id, 'role_id' => $role->id],
                    ['assigned_by' => $owner->id]
                );
            }

            if ($designationCode === 'TCHR') {
                $teacherUsers[] = $staffUser;
                TeacherProfile::query()->updateOrCreate(
                    ['school_id' => $school->id, 'employee_id' => $employees[$index + 1]->id],
                    [
                        'teacher_no' => 'TCHR-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                        'specialization' => $specialization,
                        'qualification' => 'M.Ed',
                        'experience_years' => 5 + $index,
                        'joined_teaching_on' => '2022-01-01',
                        'bio' => 'Five-year demo teacher profile.',
                        'status' => 'active',
                    ]
                );
            }
        }

        if ($teacherUsers === []) {
            $teacherUsers[] = $owner;
        }

        return [$employees, $teacherUsers, $staffUsers];
    }

    /**
     * @return array<int, Student>
     */
    private function seedStudents(School $school): array
    {
        $students = [];
        $firstNames = ['Ayaan', 'Maliha', 'Rayan', 'Samiha', 'Zarif', 'Nabila', 'Tasin', 'Anika', 'Rafi', 'Tania', 'Sajid', 'Mehzabin'];
        $lastNames = ['Rahman', 'Islam', 'Hossain', 'Ahmed'];

        for ($i = 1; $i <= 48; $i++) {
            $name = $firstNames[($i - 1) % count($firstNames)].' '.$lastNames[(int) floor(($i - 1) / count($firstNames)) % count($lastNames)];
            $guardian = Guardian::query()->updateOrCreate(
                ['school_id' => $school->id, 'email' => 'guardian'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'@example.com'],
                [
                    'full_name' => "Guardian {$i}",
                    'relationship' => $i % 2 === 0 ? 'Mother' : 'Father',
                    'phone' => '+88018'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                    'occupation' => $i % 3 === 0 ? 'Service' : 'Business',
                    'address' => 'Dhaka',
                    'status' => 'active',
                ]
            );

            $students[$i] = Student::query()->updateOrCreate(
                ['school_id' => $school->id, 'admission_no' => 'ADM-DEMO-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'guardian_id' => $guardian->id,
                    'full_name' => $name,
                    'father_name' => "Father {$i}",
                    'mother_name' => "Mother {$i}",
                    'email' => 'student'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'@example.com',
                    'phone' => '+88019'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'religion' => 'Islam',
                    'date_of_birth' => 2013 + ($i % 5).'-03-10',
                    'admitted_on' => '2017-01-01',
                    'address' => 'Dhaka',
                    'medical_notes' => $i % 17 === 0 ? 'Asthma watch' : 'No known issues',
                    'status' => $i > 44 ? 'graduated' : 'active',
                ]
            );
        }

        return $students;
    }

    /**
     * @param  array<int, Student>  $students
     * @param  array<int, AcademicYear>  $years
     * @param  array<int, AcademicClass>  $classes
     * @param  array<string, AcademicSection>  $sections
     * @param  array<int, StudentGroup>  $groups
     * @param  array<int, Shift>  $shifts
     * @return array<int, array<int, StudentEnrollment>>
     */
    private function seedEnrollments(School $school, array $students, array $years, array $classes, array $sections, array $groups, array $shifts): array
    {
        $enrollments = [];

        foreach ($students as $studentIndex => $student) {
            foreach ($years as $year => $academicYear) {
                $classIndex = min(5, max(1, ($year - 2017) + 1 + (($studentIndex - 1) % 2)));
                $sectionCode = 'C'.$classIndex.'-'.($studentIndex % 2 === 0 ? 'B' : 'A');
                $status = $year < 2026 ? 'completed' : ($studentIndex > 44 ? 'archived' : 'active');
                $enrollments[$year][$studentIndex] = StudentEnrollment::query()->updateOrCreate(
                    ['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $academicYear->id],
                    [
                        'academic_class_id' => $classes[$classIndex]->id,
                        'academic_section_id' => $sections[$sectionCode]->id,
                        'student_group_id' => $groups[($studentIndex % 2) + 1]->id,
                        'shift_id' => $shifts[($studentIndex % 2) + 1]->id,
                        'roll_no' => (string) $studentIndex,
                        'enrolled_on' => "{$year}-01-05",
                        'status' => $status,
                        'notes' => "Demo enrollment for {$year}",
                    ]
                );
            }
        }

        return $enrollments;
    }

    private function seedTimetable(School $school, array $years, array $classes, array $shifts, array $subjects, array $teacherUsers): void
    {
        foreach ($years as $year => $academicYear) {
            foreach ($classes as $classIndex => $class) {
                foreach ([1, 2, 3] as $period) {
                    TimetablePeriod::query()->updateOrCreate(
                        [
                            'school_id' => $school->id,
                            'academic_year_id' => $academicYear->id,
                            'academic_class_id' => $class->id,
                            'shift_id' => $shifts[1]->id,
                            'day_of_week' => ($period + $classIndex) % 5,
                            'period_number' => $period,
                        ],
                        [
                            'start_time' => sprintf('%02d:00', 7 + $period),
                            'end_time' => sprintf('%02d:45', 7 + $period),
                            'subject_id' => $subjects[(($period + $classIndex - 2) % count($subjects)) + 1]->id,
                            'teacher_user_id' => $teacherUsers[($period + $classIndex - 2) % count($teacherUsers)]->id,
                            'room' => "{$classIndex}0{$period}",
                            'status' => 'active',
                        ]
                    );
                }
            }
        }
    }

    private function seedAssignments(School $school, User $user, array $years, array $classes, array $subjects, array $enrollments): void
    {
        foreach ($years as $year => $academicYear) {
            foreach ($classes as $classIndex => $class) {
                foreach ([1, 2] as $assignmentIndex) {
                    $assignment = Assignment::query()->updateOrCreate(
                        ['school_id' => $school->id, 'title' => "AY {$year} Class {$classIndex} Assignment {$assignmentIndex}"],
                        [
                            'academic_class_id' => $class->id,
                            'subject_id' => $subjects[$assignmentIndex]->id,
                            'assigned_by' => $user->id,
                            'description' => 'Five-year demo classroom task.',
                            'due_date' => "{$year}-04-".str_pad((string) (10 + $assignmentIndex), 2, '0', STR_PAD_LEFT),
                            'attachment_path' => null,
                            'is_published' => true,
                            'status' => 'active',
                        ]
                    );

                    foreach (array_slice($enrollments[$year], 0, 12, true) as $studentIndex => $enrollment) {
                        if ($enrollment->academic_class_id !== $class->id) {
                            continue;
                        }

                        AssignmentSubmission::query()->updateOrCreate(
                            ['school_id' => $school->id, 'assignment_id' => $assignment->id, 'student_enrollment_id' => $enrollment->id],
                            [
                                'submitted_at' => CarbonImmutable::create($year, 4, 8 + $assignmentIndex, 10),
                                'marks_awarded' => 65 + (($studentIndex + $assignmentIndex) % 31),
                                'feedback' => 'Seeded five-year classroom work.',
                                'status' => $studentIndex % 7 === 0 ? 'late' : 'graded',
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * @return array{0: array<int, array<int, Exam>>, 1: array<int, array<int, ExamSchedule>>}
     */
    private function seedExams(School $school, User $user, array $years, array $classSubjects): array
    {
        $examTypes = [];
        foreach ([['Mid Term', 'MID', 40], ['Final Term', 'FIN', 60]] as $sort => [$name, $code, $weight]) {
            $examTypes[$code] = ExamType::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $name, 'weightage_percent' => $weight, 'description' => "{$name} assessment", 'sort_order' => $sort + 1, 'status' => 'active']
            );
        }

        $exams = [];
        $schedules = [];
        foreach ($years as $year => $academicYear) {
            foreach ($examTypes as $typeCode => $examType) {
                $exam = Exam::query()->updateOrCreate(
                    ['school_id' => $school->id, 'code' => "{$typeCode}-{$year}"],
                    [
                        'exam_type_id' => $examType->id,
                        'academic_year_id' => $academicYear->id,
                        'name' => "{$examType->name} {$year}",
                        'starts_on' => $typeCode === 'MID' ? "{$year}-06-01" : "{$year}-11-20",
                        'ends_on' => $typeCode === 'MID' ? "{$year}-06-10" : "{$year}-11-30",
                        'is_published' => true,
                        'published_at' => CarbonImmutable::create($year, $typeCode === 'MID' ? 6 : 11, 15),
                        'published_by' => $user->id,
                        'status' => $year < 2026 || $typeCode === 'MID' ? 'completed' : 'scheduled',
                        'notes' => 'Five-year demo exam',
                    ]
                );
                $exams[$year][] = $exam;

                foreach ($classSubjects as $key => $classSubject) {
                    if (! str_ends_with($key, '-1') && ! str_ends_with($key, '-2')) {
                        continue;
                    }

                    $schedule = ExamSchedule::query()->updateOrCreate(
                        ['school_id' => $school->id, 'exam_id' => $exam->id, 'class_subject_id' => $classSubject->id],
                        [
                            'exam_date' => $typeCode === 'MID' ? "{$year}-06-03" : "{$year}-11-23",
                            'starts_at' => '09:00',
                            'ends_at' => '11:00',
                            'room' => 'Exam Hall '.substr($key, 0, 1),
                            'instructions' => 'Bring pencils and admit card.',
                            'status' => $year < 2026 || $typeCode === 'MID' ? 'completed' : 'scheduled',
                        ]
                    );
                    $schedules[$year][] = $schedule;
                }
            }
        }

        return [$exams, $schedules];
    }

    private function seedMarksAndResults(School $school, User $user, array $exams, array $examSchedules, array $enrollments): void
    {
        foreach ($exams as $year => $yearExams) {
            foreach ($yearExams as $exam) {
                $scheduleSet = array_filter($examSchedules[$year] ?? [], fn (ExamSchedule $schedule): bool => $schedule->exam_id === $exam->id);

                foreach ($enrollments[$year] as $studentIndex => $enrollment) {
                    $total = 0;
                    $full = 0;
                    foreach ($scheduleSet as $schedule) {
                        $score = 48 + (($studentIndex * 7 + $exam->id + $schedule->id) % 49);
                        $isAbsent = $studentIndex % 23 === 0 && $exam->status === 'completed';
                        MarksEntry::query()->updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'exam_id' => $exam->id,
                                'class_subject_id' => $schedule->class_subject_id,
                                'student_enrollment_id' => $enrollment->id,
                            ],
                            [
                                'marks_obtained' => $isAbsent ? null : $score,
                                'full_marks' => 100,
                                'pass_marks' => 40,
                                'is_absent' => $isAbsent,
                                'absent_reason' => $isAbsent ? 'Medical leave' : null,
                                'verification_status' => $exam->status === 'scheduled' ? 'pending' : 'verified',
                                'entered_by' => $user->id,
                                'verified_by' => $exam->status === 'scheduled' ? null : $user->id,
                                'verified_at' => $exam->status === 'scheduled' ? null : now(),
                                'voided' => false,
                                'remarks' => 'Five-year seeded marks',
                            ]
                        );
                        $total += $isAbsent ? 0 : $score;
                        $full += 100;
                    }

                    if ($full > 0) {
                        $percentage = round(($total / $full) * 100, 2);
                        ResultSummary::query()->updateOrCreate(
                            ['exam_id' => $exam->id, 'student_enrollment_id' => $enrollment->id],
                            [
                                'school_id' => $school->id,
                                'total_marks_obtained' => $total,
                                'total_full_marks' => $full,
                                'percentage' => $percentage,
                                'gpa' => $percentage >= 80 ? 5 : ($percentage >= 70 ? 4 : ($percentage >= 60 ? 3.5 : 2.5)),
                                'grade' => $percentage >= 80 ? 'A+' : ($percentage >= 70 ? 'A' : ($percentage >= 60 ? 'A-' : 'B')),
                                'position_in_class' => ($studentIndex % 35) + 1,
                                'is_pass' => $percentage >= 40,
                                'computed_at' => now(),
                            ]
                        );
                    }
                }
            }
        }

        foreach ([['A+', 80, 100, 5], ['A', 70, 79, 4], ['A-', 60, 69, 3.5], ['B', 50, 59, 3], ['F', 0, 39, 0]] as [$code, $min, $max, $point]) {
            GradeScale::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $code === 'F' ? 'Fail' : 'Pass', 'min_percent' => $min, 'max_percent' => $max, 'grade_point' => $point, 'fail_below_percent' => 40, 'gpa_calculation_method' => 'weighted', 'status' => 'active']
            );
        }
    }

    private function seedAttendance(School $school, User $user, array $enrollments, array $employees, array $years): void
    {
        foreach ($years as $year => $academicYear) {
            foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $month) {
                foreach ([5, 12, 19] as $day) {
                    foreach (array_slice($enrollments[$year], 0, 36, true) as $studentIndex => $enrollment) {
                        $status = match (true) {
                            $studentIndex % 19 === 0 => 'absent',
                            $studentIndex % 11 === 0 => 'late',
                            default => 'present',
                        };
                        StudentAttendanceRecord::query()->updateOrCreate(
                            ['school_id' => $school->id, 'student_enrollment_id' => $enrollment->id, 'attendance_date' => CarbonImmutable::create($year, $month, $day)->toDateString()],
                            ['status' => $status, 'late_arrival_time' => $status === 'late' ? '08:20' : null, 'half_day' => false, 'remarks' => 'Five-year demo attendance']
                        );
                    }

                    foreach ($employees as $employeeIndex => $employee) {
                        $staffStatus = $employeeIndex % 7 === 0 ? 'late' : 'present';
                        EmployeeAttendanceRecord::query()->updateOrCreate(
                            ['school_id' => $school->id, 'employee_id' => $employee->id, 'date' => CarbonImmutable::create($year, $month, $day)->toDateString()],
                            [
                                'status' => $staffStatus,
                                'check_in_time' => $staffStatus === 'late' ? '08:20' : '07:50',
                                'check_out_time' => '15:00',
                                'notes' => 'Five-year demo staff attendance',
                                'recorded_by' => $user->id,
                            ]
                        );
                    }
                }
            }
        }
    }

    private function seedFinance(School $school, User $user, array $years, array $classes, array $groups, array $enrollments): void
    {
        $feeCategories = [];
        foreach ([['Tuition Fee', 'TUITION', 'monthly'], ['Exam Fee', 'EXAM', 'per_exam'], ['Transport Fee', 'TRANSPORT', 'optional']] as $sort => [$name, $code, $billingType]) {
            $feeCategories[$code] = FeeCategory::query()->updateOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $name, 'description' => "{$name} demo category", 'sort_order' => $sort + 1, 'billing_type' => $billingType, 'status' => 'active']
            );
        }

        foreach ($years as $year => $academicYear) {
            foreach ($classes as $classIndex => $class) {
                FeeStructure::query()->updateOrCreate(
                    ['school_id' => $school->id, 'fee_category_id' => $feeCategories['TUITION']->id, 'academic_year_id' => $academicYear->id, 'academic_class_id' => $class->id],
                    [
                        'student_group_id' => $groups[1]->id,
                        'amount' => 2000 + ($classIndex * 350) + (($year - 2017) * 100),
                        'due_day_of_month' => 10,
                        'months_applicable' => $this->monthList($year),
                        'is_recurring' => true,
                        'status' => 'active',
                    ]
                );
            }

            foreach ($this->monthList($year, $year === 2026 ? 4 : 12) as $monthIndex => $month) {
                foreach (array_slice($enrollments[$year], 0, 30, true) as $studentIndex => $enrollment) {
                    $amount = 2200 + (($studentIndex % 5) * 200);
                    $paid = match (true) {
                        $studentIndex % 13 === 0 && $monthIndex > 1 => $amount / 2,
                        $studentIndex % 17 === 0 && $monthIndex > 2 => 0,
                        default => $amount,
                    };
                    $status = $paid >= $amount ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
                    $invoice = StudentInvoice::query()->updateOrCreate(
                        ['school_id' => $school->id, 'invoice_no' => 'INV-DEMO-'.$month.'-'.str_pad((string) $studentIndex, 3, '0', STR_PAD_LEFT)],
                        [
                            'student_enrollment_id' => $enrollment->id,
                            'academic_year_id' => $years[$year]->id,
                            'fee_month' => $month,
                            'subtotal' => $amount,
                            'discount' => 0,
                            'discount_breakdown' => [],
                            'total' => $amount,
                            'paid_amount' => $paid,
                            'due_date' => "{$month}-10",
                            'status' => $status,
                            'notes' => 'Five-year demo invoice',
                        ]
                    );

                    if ($paid > 0) {
                        InvoicePayment::query()->updateOrCreate(
                            ['school_id' => $school->id, 'student_invoice_id' => $invoice->id, 'transaction_ref' => 'PAY-DEMO-'.$month.'-'.str_pad((string) $studentIndex, 3, '0', STR_PAD_LEFT)],
                            [
                                'amount' => $paid,
                                'paid_on' => "{$month}-08",
                                'payment_method' => $studentIndex % 2 === 0 ? 'bkash' : 'cash',
                                'payment_channel_metadata' => ['mode' => 'demo'],
                                'notes' => 'Five-year demo payment',
                            ]
                        );
                    }
                }
            }
        }

        foreach (['bkash', 'nagad', 'sslcommerz', 'stripe'] as $gateway) {
            PaymentGatewayConfig::query()->updateOrCreate(
                ['school_id' => $school->id, 'gateway' => $gateway],
                [
                    'credentials_encrypted' => ['merchant_id' => "demo-{$gateway}", 'public_key' => 'demo-public', 'secret_key' => 'demo-secret'],
                    'is_active' => $gateway !== 'stripe',
                    'test_mode' => true,
                ]
            );
        }

        $discountPolicy = \App\Models\DiscountPolicy::query()->updateOrCreate(
            ['school_id' => $school->id, 'code' => 'MERIT-25'],
            [
                'name' => 'Merit Waiver',
                'discount_type' => 'percent',
                'amount' => 25,
                'applies_to_category_ids' => [$feeCategories['TUITION']->id],
                'is_stackable' => false,
                'status' => 'active',
            ]
        );

        foreach ([1, 7, 13, 19] as $studentIndex) {
            if (!isset($enrollments[2026][$studentIndex])) {
                continue;
            }

            StudentDiscount::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_enrollment_id' => $enrollments[2026][$studentIndex]->id,
                    'discount_policy_id' => $discountPolicy->id,
                    'academic_year_id' => $years[2026]->id,
                ],
                [
                    'approved_by' => $user->id,
                    'notes' => 'Seeded merit discount for QA.',
                ]
            );
        }
    }

    private function seedStaffOperations(School $school, User $user, array $years, array $employees): void
    {
        $leaveType = LeaveType::query()->updateOrCreate(
            ['school_id' => $school->id, 'code' => 'CL'],
            ['name' => 'Casual Leave', 'max_days_per_year' => 10, 'is_paid' => true, 'requires_approval' => true, 'status' => 'active']
        );

        foreach ($years as $year => $academicYear) {
            foreach ($employees as $employeeIndex => $employee) {
                LeaveBalance::query()->updateOrCreate(
                    ['school_id' => $school->id, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'academic_year_id' => $academicYear->id],
                    ['total_days' => 10, 'used_days' => $employeeIndex % 4, 'remaining_days' => 10 - ($employeeIndex % 4)]
                );

                LeaveApplication::query()->updateOrCreate(
                    ['school_id' => $school->id, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'from_date' => "{$year}-04-25"],
                    [
                        'to_date' => "{$year}-04-25",
                        'total_days' => 1,
                        'reason' => 'Five-year demo leave request',
                        'status' => $employeeIndex % 5 === 0 && $year === 2026 ? 'pending' : 'approved',
                        'applied_at' => CarbonImmutable::create($year, 4, 20),
                        'reviewed_by' => $employeeIndex % 5 === 0 && $year === 2026 ? null : $user->id,
                        'reviewed_at' => $employeeIndex % 5 === 0 && $year === 2026 ? null : CarbonImmutable::create($year, 4, 21),
                        'review_note' => 'Seeded review',
                    ]
                );

                foreach ($this->monthList($year, $year === 2026 ? 4 : 12) as $month) {
                    $basic = (float) $employee->salary;
                    $gross = $basic + 6500;
                    $deductions = 3000;
                    SalaryRecord::query()->updateOrCreate(
                        ['school_id' => $school->id, 'employee_id' => $employee->id, 'month' => $month],
                        [
                            'academic_year_id' => $academicYear->id,
                            'basic_amount' => $basic,
                            'allowances' => ['house_rent' => 5000, 'medical' => 1500],
                            'gross_amount' => $gross,
                            'deductions' => ['provident_fund' => 2000, 'income_tax' => 1000],
                            'total_deductions' => $deductions,
                            'net_amount' => $gross - $deductions,
                            'paid_at' => "{$month}-25",
                            'payment_method' => 'bank',
                            'transaction_ref' => 'SAL-DEMO-'.$month.'-'.$employee->employee_no,
                            'notes' => 'Five-year demo salary',
                            'status' => $year === 2026 && $month === '2026-04' && $employeeIndex % 6 === 0 ? 'pending' : 'paid',
                        ]
                    );
                }
            }
        }
    }

    private function seedPromotions(School $school, User $user, array $years, array $classes, array $enrollments): void
    {
        foreach ([2022, 2023, 2024, 2025] as $year) {
            $fromClass = $classes[min(5, $year - 2021)];
            $toClass = $classes[min(5, $year - 2020)];
            $batch = PromotionBatch::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'from_academic_year_id' => $years[$year]->id,
                    'to_academic_year_id' => $years[$year + 1]->id,
                    'from_academic_class_id' => $fromClass->id,
                    'to_academic_class_id' => $toClass->id,
                ],
                ['status' => 'completed', 'processed_count' => 24, 'created_by' => $user->id, 'processed_at' => CarbonImmutable::create($year, 12, 20)]
            );

            foreach (array_slice($enrollments[$year], 0, 24, true) as $studentIndex => $enrollment) {
                PromotionRecord::query()->updateOrCreate(
                    ['school_id' => $school->id, 'promotion_batch_id' => $batch->id, 'student_enrollment_id' => $enrollment->id],
                    [
                        'action' => $studentIndex % 12 === 0 ? 'retained' : 'promoted',
                        'new_enrollment_id' => $enrollments[$year + 1][$studentIndex]->id ?? null,
                        'notes' => 'Five-year promotion history',
                        'processed_by' => $user->id,
                    ]
                );
            }
        }
    }

    private function seedCalendarDocumentsAndAudit(School $school, User $user, array $years, array $classes): void
    {
        foreach ($years as $year => $academicYear) {
            foreach ([['Book Fair', 2, false], ['Annual Sports', 3, false], ['Eid Holiday', 4, true], ['Parents Meeting', 7, false]] as [$title, $month, $holiday]) {
                CalendarEvent::query()->updateOrCreate(
                    ['school_id' => $school->id, 'title' => "Demo {$title} {$year}", 'starts_on' => "{$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'-15'],
                    [
                        'academic_year_id' => $academicYear->id,
                        'academic_class_id' => $classes[1]->id,
                        'description' => "Five-year {$title} event",
                        'ends_on' => "{$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'-15',
                        'starts_at' => $holiday ? null : '10:00',
                        'ends_at' => $holiday ? null : '12:00',
                        'location' => $holiday ? null : 'Campus',
                        'is_holiday' => $holiday,
                        'recurring_rule' => null,
                        'status' => 'active',
                        'created_by' => $user->id,
                    ]
                );
            }

            Storage::disk('local')->put("demo/demo-circular-{$year}.txt", "Demo circular for {$year} School SaaS Enterprise browser verification.");
            SchoolDocument::query()->updateOrCreate(
                ['school_id' => $school->id, 'title' => "Demo Circular {$year}"],
                [
                    'uploader_id' => $user->id,
                    'category' => 'circular',
                    'file_path' => "demo/demo-circular-{$year}.txt",
                    'file_name' => "demo-circular-{$year}.txt",
                    'file_size_bytes' => strlen("Demo circular for {$year} School SaaS Enterprise browser verification."),
                    'mime_type' => 'text/plain',
                    'is_public' => true,
                    'uploaded_at' => CarbonImmutable::create($year, 1, 15),
                ]
            );

            AuditLog::query()->updateOrCreate(
                ['school_id' => $school->id, 'event' => "demo.year.closed.{$year}"],
                [
                    'actor_id' => $user->id,
                    'auditable_type' => AcademicYear::class,
                    'auditable_id' => $academicYear->id,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'DemoDataSeeder',
                    'metadata' => ['year' => $year, 'source' => 'ten-year-demo'],
                ]
            );
        }
    }

    /**
     * @param  array<string, User>  $staffUsers
     * @param  array<int, Student>  $students
     * @return array<string, User>
     */
    private function seedAccessUsers(School $school, User $owner, array $staffUsers, array $students): array
    {
        $roleUsers = ['school-owner' => $owner];

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'superadmin@example.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('password')]
        );
        SchoolMembership::query()->updateOrCreate(
            ['school_id' => $school->id, 'user_id' => $superAdmin->id],
            ['status' => 'active', 'joined_at' => CarbonImmutable::create(2017, 1, 1)]
        );
        $superRole = Role::query()->where('key', 'super-admin')->first();
        if ($superRole) {
            UserRoleAssignment::query()->updateOrCreate(
                ['school_id' => $school->id, 'user_id' => $superAdmin->id, 'role_id' => $superRole->id],
                ['assigned_by' => $owner->id]
            );
        }
        $roleUsers['super-admin'] = $superAdmin;

        if (isset($staffUsers['offc'])) {
            $roleUsers['school-admin'] = $staffUsers['offc'];
        }
        if (isset($staffUsers['prin'])) {
            $roleUsers['principal'] = $staffUsers['prin'];
        }
        if (isset($staffUsers['acct'])) {
            $roleUsers['accountant'] = $staffUsers['acct'];
        }

        $teacherUser = collect($staffUsers)
            ->filter(fn (User $user, string $key): bool => str_contains($key, 'amina-rahman') || str_contains($key, 'karim-uddin'))
            ->first();
        if ($teacherUser instanceof User) {
            $roleUsers['teacher'] = $teacherUser;
        }

        $student = $students[1];
        $studentUser = User::query()->updateOrCreate(
            ['email' => $student->email],
            ['name' => $student->full_name, 'password' => bcrypt('password')]
        );
        SchoolMembership::query()->updateOrCreate(
            ['school_id' => $school->id, 'user_id' => $studentUser->id],
            ['status' => 'active', 'joined_at' => CarbonImmutable::create(2017, 1, 1)]
        );
        $studentRole = Role::query()->where('key', 'student')->first();
        if ($studentRole) {
            UserRoleAssignment::query()->updateOrCreate(
                ['school_id' => $school->id, 'user_id' => $studentUser->id, 'role_id' => $studentRole->id],
                ['assigned_by' => $owner->id]
            );
        }
        $roleUsers['student'] = $studentUser;

        $guardian = $student->guardian()->firstOrFail();
        $parentUser = User::query()->updateOrCreate(
            ['email' => $guardian->email],
            ['name' => $guardian->full_name, 'password' => bcrypt('password')]
        );
        SchoolMembership::query()->updateOrCreate(
            ['school_id' => $school->id, 'user_id' => $parentUser->id],
            ['status' => 'active', 'joined_at' => CarbonImmutable::create(2017, 1, 1)]
        );
        $parentRole = Role::query()->where('key', 'parent')->first();
        if ($parentRole) {
            UserRoleAssignment::query()->updateOrCreate(
                ['school_id' => $school->id, 'user_id' => $parentUser->id, 'role_id' => $parentRole->id],
                ['assigned_by' => $owner->id]
            );
        }
        $roleUsers['parent'] = $parentUser;

        $auditor = User::query()->updateOrCreate(
            ['email' => 'auditor@example.com'],
            ['name' => 'Read Only Auditor', 'password' => bcrypt('password')]
        );
        SchoolMembership::query()->updateOrCreate(
            ['school_id' => $school->id, 'user_id' => $auditor->id],
            ['status' => 'active', 'joined_at' => CarbonImmutable::create(2017, 1, 1)]
        );
        $auditorRole = Role::query()->where('key', 'read-only-auditor')->first();
        if ($auditorRole) {
            UserRoleAssignment::query()->updateOrCreate(
                ['school_id' => $school->id, 'user_id' => $auditor->id, 'role_id' => $auditorRole->id],
                ['assigned_by' => $owner->id]
            );
        }
        $roleUsers['read-only-auditor'] = $auditor;

        return $roleUsers;
    }

    /**
     * @param  array<string, User>  $roleUsers
     * @param  array<int, AcademicYear>  $years
     * @param  array<int, array<int, StudentEnrollment>>  $enrollments
     */
    private function seedInvitationsAndNotifications(School $school, User $owner, array $roleUsers, array $years, array $enrollments): void
    {
        $schoolAdminRole = Role::query()->where('key', 'school-admin')->first();
        if ($schoolAdminRole) {
            foreach ([1, 2, 3] as $index) {
                SchoolInvitation::query()->updateOrCreate(
                    ['school_id' => $school->id, 'email' => "invite{$index}@example.com"],
                    [
                        'role_id' => $schoolAdminRole->id,
                        'invited_by' => $owner->id,
                        'name' => "Invited Admin {$index}",
                        'token' => (string) Str::uuid(),
                        'status' => $index === 1 ? 'pending' : 'accepted',
                        'expires_at' => now()->addDays(7 + $index),
                        'accepted_by' => $index === 1 ? null : $owner->id,
                        'accepted_at' => $index === 1 ? null : now()->subDays($index),
                    ]
                );
            }
        }

        foreach ($roleUsers as $roleKey => $user) {
            SchoolNotification::query()->updateOrCreate(
                ['school_id' => $school->id, 'recipient_user_id' => $user->id, 'title' => "Quarterly update for {$roleKey}"],
                [
                    'type' => 'system',
                    'body' => "Seeded notification for {$roleKey} QA walkthroughs.",
                    'data' => ['role' => $roleKey, 'scope' => 'demo'],
                    'read_at' => in_array($roleKey, ['student', 'parent'], true) ? null : now()->subDay(),
                ]
            );
        }

        foreach ([1, 5, 9] as $studentIndex) {
            if (!isset($enrollments[2026][$studentIndex])) {
                continue;
            }

            $student = $enrollments[2026][$studentIndex]->student()->first();
            if (!$student?->email) {
                continue;
            }

            $recipient = User::query()->where('email', $student->email)->first();
            if (!$recipient) {
                continue;
            }

            SchoolNotification::query()->updateOrCreate(
                ['school_id' => $school->id, 'recipient_user_id' => $recipient->id, 'title' => "Result published {$student->admission_no}"],
                [
                    'type' => 'results',
                    'body' => 'Your latest result summary is ready for review.',
                    'data' => ['academic_year_id' => $years[2026]->id],
                    'read_at' => null,
                ]
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function monthList(int $year, int $untilMonth = 12): array
    {
        $months = [];
        for ($month = 1; $month <= $untilMonth; $month++) {
            $months[] = sprintf('%d-%02d', $year, $month);
        }

        return $months;
    }
}
