export interface ApiUser {
  id: number
  name: string
  email: string
  schools: ApiSchool[]
}

export interface ApiSchool {
  id: number
  public_id?: string
  name: string
  slug: string
  status: string
  roles?: {
    key: string
    name: string
  }[]
  permissions?: string[]
}

export interface AcademicClass {
  id: number
  school_id: number
  name: string
  code: string
  description: string | null
  sort_order: number
  status: string
}

export interface AcademicSection {
  id: number
  school_id: number
  academic_class_id: number
  academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  name: string
  code: string
  capacity: number | null
  room: string | null
  sort_order: number
  status: string
}

export interface AcademicYear {
  id: number
  school_id: number
  name: string
  code: string
  starts_on: string
  ends_on: string
  is_current: boolean
  status: string
}

export interface Subject {
  id: number
  school_id: number
  name: string
  code: string
  type: 'core' | 'elective' | 'co_curricular'
  description: string | null
  credit_hours: number | null
  sort_order: number
  status: string
}

export interface StudentGroup {
  id: number
  school_id: number
  name: string
  code: string
  description: string | null
  sort_order: number
  status: string
}

export interface Shift {
  id: number
  school_id: number
  name: string
  code: string
  starts_at: string | null
  ends_at: string | null
  description: string | null
  sort_order: number
  status: string
}

export interface ClassSubject {
  id: number
  school_id: number
  academic_class_id: number
  subject_id: number
  academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  subject?: Pick<Subject, 'id' | 'name' | 'code' | 'type'>
  full_marks: number
  pass_marks: number
  subjective_marks: number | null
  sort_order: number
  status: string
}

export interface ExamType {
  id: number
  school_id: number
  name: string
  code: string
  weightage_percent: string | null
  description: string | null
  sort_order: number
  status: string
}

export interface Exam {
  id: number
  school_id: number
  exam_type_id: number
  academic_year_id: number
  name: string
  code: string
  starts_on: string
  ends_on: string
  is_published: boolean
  published_at: string | null
  published_by: number | null
  status: 'draft' | 'scheduled' | 'completed' | 'archived'
  notes: string | null
  exam_type?: Pick<ExamType, 'id' | 'name' | 'code' | 'weightage_percent'>
  academic_year?: Pick<AcademicYear, 'id' | 'name' | 'code'>
}

export interface ExamSchedule {
  id: number
  school_id: number
  exam_id: number
  class_subject_id: number
  exam_date: string
  starts_at: string | null
  ends_at: string | null
  room: string | null
  instructions: string | null
  status: 'scheduled' | 'completed' | 'cancelled'
  exam?: Pick<Exam, 'id' | 'name' | 'code' | 'starts_on' | 'ends_on'>
  class_subject?: Pick<ClassSubject, 'id' | 'academic_class_id' | 'subject_id' | 'full_marks' | 'pass_marks'> & {
    academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
    subject?: Pick<Subject, 'id' | 'name' | 'code' | 'type'>
  }
}

export interface Designation {
  id: number
  school_id: number
  name: string
  code: string
  description: string | null
  sort_order: number
  status: string
}

export interface Employee {
  id: number
  school_id: number
  designation_id: number | null
  employee_no: string
  full_name: string
  father_name: string | null
  mother_name: string | null
  email: string | null
  phone: string | null
  gender: string | null
  religion: string | null
  date_of_birth: string | null
  joined_on: string
  salary: string
  employee_type: string
  address: string | null
  notes: string | null
  status: string
  designation?: Pick<Designation, 'id' | 'name' | 'code'> | null
}

export interface Guardian {
  id: number
  school_id: number
  full_name: string
  relationship: string
  phone: string | null
  email: string | null
  occupation: string | null
  address: string | null
  status: string
  students_count?: number
}

export interface Student {
  id: number
  school_id: number
  guardian_id: number | null
  admission_no: string
  full_name: string
  father_name: string | null
  mother_name: string | null
  email: string | null
  phone: string | null
  gender: string | null
  religion: string | null
  date_of_birth: string | null
  admitted_on: string
  address: string | null
  medical_notes: string | null
  status: string
  guardian?: Pick<Guardian, 'id' | 'full_name' | 'relationship' | 'phone'> | null
}

export interface StudentEnrollment {
  id: number
  school_id: number
  student_id: number
  academic_year_id: number
  academic_class_id: number
  academic_section_id: number | null
  student_group_id: number | null
  shift_id: number | null
  roll_no: string | null
  enrolled_on: string
  status: 'active' | 'completed' | 'transferred' | 'archived'
  notes: string | null
  student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
  academic_year?: Pick<AcademicYear, 'id' | 'name' | 'code'>
  academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  academic_section?: Pick<AcademicSection, 'id' | 'name' | 'code'> | null
  student_group?: Pick<StudentGroup, 'id' | 'name' | 'code'> | null
  shift?: Pick<Shift, 'id' | 'name' | 'code'> | null
}

export interface StudentAttendanceRecord {
  id: number
  school_id: number
  student_enrollment_id: number
  attendance_date: string
  status: 'present' | 'absent' | 'late' | 'excused'
  late_arrival_time: string | null
  half_day: boolean
  leave_reference: string | null
  remarks: string | null
  student_enrollment?: Pick<StudentEnrollment, 'id' | 'student_id' | 'academic_class_id' | 'roll_no'> & {
    student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
    academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  }
}

export interface TeacherProfile {
  id: number
  school_id: number
  employee_id: number
  teacher_no: string
  specialization: string | null
  qualification: string | null
  experience_years: number | null
  joined_teaching_on: string | null
  bio: string | null
  status: string
  employee?: Pick<Employee, 'id' | 'employee_no' | 'full_name' | 'email' | 'phone'>
}

export interface GradeScale {
  id: number
  school_id: number
  name: string
  code: string
  min_percent: string
  max_percent: string
  grade_point: string
  fail_below_percent: string | null
  gpa_calculation_method: 'weighted' | 'simple_average'
  status: string
}

export interface MarksEntry {
  id: number
  school_id: number
  exam_id: number
  class_subject_id: number
  student_enrollment_id: number
  marks_obtained: string | null
  full_marks: number
  pass_marks: number
  is_absent: boolean
  absent_reason: string | null
  verification_status: 'pending' | 'verified' | 'rejected'
  voided: boolean
  remarks: string | null
  exam?: Pick<Exam, 'id' | 'name' | 'code'>
  class_subject?: Pick<ClassSubject, 'id' | 'academic_class_id' | 'subject_id' | 'full_marks' | 'pass_marks'> & {
    subject?: Pick<Subject, 'id' | 'name' | 'code'>
    academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  }
  student_enrollment?: Pick<StudentEnrollment, 'id' | 'student_id' | 'academic_class_id' | 'roll_no'> & {
    student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
  }
}

export interface FeeCategory {
  id: number
  school_id: number
  name: string
  code: string
  description: string | null
  billing_type: 'monthly' | 'one_time' | 'per_exam' | 'optional'
  sort_order: number
  status: string
}

export interface FeeStructure {
  id: number
  school_id: number
  fee_category_id: number
  academic_year_id: number
  academic_class_id: number | null
  amount: string
  due_day_of_month: number | null
  months_applicable: string[] | null
  is_recurring: boolean
  status: string
  fee_category?: Pick<FeeCategory, 'id' | 'name' | 'code' | 'billing_type'>
  academic_year?: Pick<AcademicYear, 'id' | 'name'>
  academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'> | null
}

export interface DiscountPolicy {
  id: number
  school_id: number
  name: string
  code: string
  discount_type: 'flat' | 'percent'
  amount: string
  applies_to_category_ids: number[] | null
  is_stackable: boolean
  status: string
}

export interface StudentInvoice {
  id: number
  school_id: number
  student_enrollment_id: number
  academic_year_id: number
  invoice_no: string
  fee_month: string | null
  subtotal: string
  discount: string
  total: string
  paid_amount: string
  status: 'unpaid' | 'partial' | 'paid' | 'voided'
  due_date: string | null
  student_enrollment?: Pick<StudentEnrollment, 'id' | 'roll_no'> & {
    student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
    academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  }
}

export interface SalaryRecord {
  id: number
  school_id: number
  employee_id: number
  academic_year_id: number
  month: string
  basic_amount: string
  allowances: Record<string, number> | null
  gross_amount: string
  deductions: Record<string, number> | null
  total_deductions: string
  net_amount: string
  status: 'pending' | 'paid' | 'voided'
  employee?: Pick<Employee, 'id' | 'employee_no' | 'full_name'>
}

export interface EmployeeAttendanceRecord {
  id: number
  school_id: number
  employee_id: number
  date: string
  status: 'present' | 'absent' | 'late' | 'half_day' | 'on_leave'
  check_in_time: string | null
  check_out_time: string | null
  notes: string | null
  employee?: Pick<Employee, 'id' | 'employee_no' | 'full_name'>
}

export interface LeaveType {
  id: number
  school_id: number
  name: string
  code: string
  max_days_per_year: number
  is_paid: boolean
  requires_approval: boolean
  status: string
}

export interface LeaveBalance {
  id: number
  school_id: number
  employee_id: number
  leave_type_id: number
  academic_year_id: number
  total_days: number
  used_days: number
  remaining_days: number
  employee?: Pick<Employee, 'id' | 'employee_no' | 'full_name'>
  leave_type?: Pick<LeaveType, 'id' | 'name' | 'code'>
  academic_year?: Pick<AcademicYear, 'id' | 'name'>
}

export interface LeaveApplication {
  id: number
  school_id: number
  employee_id: number
  leave_type_id: number
  from_date: string
  to_date: string
  total_days: number
  reason: string
  status: 'pending' | 'approved' | 'rejected' | 'cancelled'
  review_note: string | null
  employee?: Pick<Employee, 'id' | 'employee_no' | 'full_name'>
  leave_type?: Pick<LeaveType, 'id' | 'name' | 'code'>
}

export interface ResultSummary {
  id: number
  school_id: number
  exam_id: number
  student_enrollment_id: number
  total_marks_obtained: string
  total_full_marks: string
  percentage: string
  gpa: string
  grade: string | null
  position_in_class: number | null
  is_pass: boolean
  computed_at: string
  student_enrollment?: Pick<StudentEnrollment, 'id' | 'roll_no'> & {
    student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
    academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  }
}

export type PromotionAction = 'promoted' | 'retained' | 'transferred_out' | 'graduated' | 'dropped'

export interface PromotionPreviewRow {
  student_enrollment_id: number
  student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
  suggested_action: PromotionAction
}

export interface PromotionRecord {
  id: number
  school_id: number
  promotion_batch_id: number
  student_enrollment_id: number
  action: PromotionAction
  new_enrollment_id: number | null
  notes: string | null
  processed_by: number | null
  student_enrollment?: Pick<StudentEnrollment, 'id' | 'student_id' | 'roll_no' | 'status'> & {
    student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
  }
  new_enrollment?: StudentEnrollment | null
}

export interface PromotionBatch {
  id: number
  school_id: number
  from_academic_year_id: number
  to_academic_year_id: number
  from_academic_class_id: number
  to_academic_class_id: number
  status: 'draft' | 'in_progress' | 'completed' | 'rolled_back'
  processed_count: number
  created_by: number | null
  processed_at: string | null
  records?: PromotionRecord[]
}

export interface CalendarEvent {
  id: number
  school_id: number
  academic_year_id: number | null
  academic_class_id: number | null
  title: string
  description: string | null
  starts_on: string
  ends_on: string | null
  starts_at: string | null
  ends_at: string | null
  location: string | null
  is_holiday: boolean
  recurring_rule: string | null
  status: 'active' | 'cancelled'
  academic_year?: Pick<AcademicYear, 'id' | 'name' | 'code'> | null
  academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'> | null
  creator?: Pick<ApiUser, 'id' | 'name' | 'email'> | null
}

export interface SchoolDocument {
  id: number
  school_id: number
  uploader_id: number
  category: 'circular' | 'student_document' | 'employee_document' | 'financial_document' | 'other'
  title: string
  file_name: string
  file_size_bytes: number
  mime_type: string
  is_public: boolean
  related_model_type: string | null
  related_model_id: number | null
  uploaded_at: string
  download_url?: string
  uploader?: Pick<ApiUser, 'id' | 'name' | 'email'> | null
}

export interface ReportExport {
  id: number
  job_id: string
  school_id: number
  requested_by: number
  type: string
  status: 'pending' | 'processing' | 'completed' | 'failed'
  target_type: string | null
  target_id: number | null
  parameters: Record<string, unknown> | null
  file_name: string | null
  completed_at: string | null
  error: string | null
}

export interface ReportDownloadStatus {
  job_id: string
  type: string
  status: 'pending' | 'processing' | 'completed' | 'failed'
  file_name: string | null
  download_url: string | null
}

export interface StudentAttendanceSummary {
  student_enrollment: Pick<StudentEnrollment, 'id' | 'roll_no'> & {
    student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
    academic_class?: Pick<AcademicClass, 'id' | 'name' | 'code'>
  }
  present: number
  absent: number
  late: number
  half_day: number
  total: number
  attendance_percentage: number
}

export interface DashboardSummary {
  admin: {
    student_count: number
    employee_count: number
    today_attendance_rate: number
    fee_collection_this_month: number
    fee_collection_last_month: number
    pending_leave_applications: number
    upcoming_exams: Pick<Exam, 'id' | 'name' | 'starts_on'>[]
    attendance_concerns: {
      student_enrollment?: Pick<StudentEnrollment, 'id' | 'roll_no'> & {
        student?: Pick<Student, 'id' | 'admission_no' | 'full_name'>
      }
      attendance_percentage: number
    }[]
  }
  accountant: {
    collection_trend: { month: string, total: string }[]
    outstanding_by_class: { id: number, name: string, outstanding: string }[]
    unpaid_invoices: number
    pending_salaries: number
  }
  teacher: {
    pending_marks_entries: number
    upcoming_exams: Pick<Exam, 'id' | 'name' | 'starts_on'>[]
  }
  auditor: {
    recent_audit_logs: { id: number, event: string, actor_id: number | null, created_at: string }[]
  }
}

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE'
  body?: BodyInit | Record<string, unknown>
}

export function useApi() {
  const config = useRuntimeConfig()
  const token = useState<string | null>('auth.token', () => null)

  async function request<T>(path: string, options: RequestOptions = {}) {
    const headers: Record<string, string> = {
      Accept: 'application/json',
    }

    if (token.value) {
      headers.Authorization = `Bearer ${token.value}`
    }

    return await $fetch<T>(`${config.public.apiBase}${path}`, {
      method: options.method || 'GET',
      body: options.body,
      headers,
    })
  }

  return { request }
}
