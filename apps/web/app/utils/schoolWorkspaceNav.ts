export type SchoolWorkspaceTone = 'academic' | 'people' | 'finance' | 'operations'

export type SchoolWorkspaceModule = {
  label: string
  description: string
  route: string
  permissions: string[]
  tone: SchoolWorkspaceTone
}

export const schoolWorkspaceModules: SchoolWorkspaceModule[] = [
  { label: 'Academic Years', description: 'Sessions and current year', route: 'academic-years', permissions: ['academic_years.manage'], tone: 'academic' },
  { label: 'Classes', description: 'Class levels and order', route: 'academic-classes', permissions: ['academic_classes.manage'], tone: 'academic' },
  { label: 'Sections', description: 'Rooms, capacity, class mapping', route: 'academic-sections', permissions: ['sections.manage'], tone: 'academic' },
  { label: 'Subjects', description: 'Core and co-curricular subjects', route: 'subjects', permissions: ['subjects.manage'], tone: 'academic' },
  { label: 'Class Subjects', description: 'Marks and subject assignment', route: 'class-subjects', permissions: ['class_subjects.manage'], tone: 'academic' },
  { label: 'Groups', description: 'Science and general tracks', route: 'student-groups', permissions: ['student_groups.manage'], tone: 'academic' },
  { label: 'Shifts', description: 'Morning and day operations', route: 'shifts', permissions: ['shifts.manage'], tone: 'academic' },
  { label: 'Timetable', description: 'Periods, rooms, teachers', route: 'timetable', permissions: ['timetable.manage'], tone: 'academic' },
  { label: 'Assignments', description: 'Class work and submissions', route: 'assignments', permissions: ['assignments.manage'], tone: 'academic' },
  { label: 'Students', description: 'Admissions and profiles', route: 'students', permissions: ['students.manage'], tone: 'people' },
  { label: 'Enrollments', description: 'Year, class, roll, section', route: 'enrollments', permissions: ['enrollments.manage'], tone: 'people' },
  { label: 'Teachers', description: 'Teaching profiles', route: 'teacher-profiles', permissions: ['teachers.manage'], tone: 'people' },
  { label: 'Attendance', description: 'Student daily records', route: 'attendance', permissions: ['attendance.manage'], tone: 'people' },
  { label: 'Designations', description: 'Staff role catalog', route: 'designations', permissions: ['designations.manage'], tone: 'people' },
  { label: 'Employees', description: 'Staff records and salary base', route: 'employees', permissions: ['employees.manage'], tone: 'people' },
  { label: 'Exams', description: 'Schedules and publication', route: 'exams', permissions: ['exams.manage'], tone: 'operations' },
  { label: 'Marks', description: 'Entry and verification', route: 'marks', permissions: ['marks.enter.any', 'marks.enter.own'], tone: 'operations' },
  { label: 'Reports', description: 'Results, PDFs, attendance', route: 'reports', permissions: ['reports.view'], tone: 'operations' },
  { label: 'Promotions', description: 'Year-end progression', route: 'promotions', permissions: ['promotions.manage'], tone: 'operations' },
  { label: 'Calendar', description: 'Events and holidays', route: 'calendar', permissions: ['calendar.manage', 'reports.view'], tone: 'operations' },
  { label: 'Documents', description: 'Circulars and files', route: 'documents', permissions: ['documents.manage'], tone: 'operations' },
  { label: 'Finance', description: 'Fees, invoices, payments', route: 'finance', permissions: ['finance.manage'], tone: 'finance' },
  { label: 'Payment Gateways', description: 'bKash, Nagad, cards', route: 'payment-gateways', permissions: ['payment_gateways.manage'], tone: 'finance' },
  { label: 'Staff Ops', description: 'Payroll, attendance, leave', route: 'staff-operations', permissions: ['payroll.manage', 'employee_attendance.manage', 'leave.manage'], tone: 'finance' },
]

export const schoolWorkspaceGroups = [
  { title: 'Academics', tone: 'academic' },
  { title: 'People', tone: 'people' },
  { title: 'Operations', tone: 'operations' },
  { title: 'Finance', tone: 'finance' },
] as const
