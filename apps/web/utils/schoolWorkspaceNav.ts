export type SchoolWorkspaceTone = 'academic' | 'people' | 'finance' | 'operations'

export type SchoolWorkspaceModule = {
  label: string
  description: string
  route: string
  permissions: string[]
  tone: SchoolWorkspaceTone
  icon: string
}

export const schoolWorkspaceModules: SchoolWorkspaceModule[] = [
  { label: 'Academic Years', description: 'Sessions and current year', route: 'academic-years', permissions: ['academic_years.manage'], tone: 'academic', icon: 'calendar' },
  { label: 'Classes', description: 'Class levels and order', route: 'academic-classes', permissions: ['academic_classes.manage'], tone: 'academic', icon: 'stack' },
  { label: 'Sections', description: 'Rooms, capacity, class mapping', route: 'academic-sections', permissions: ['sections.manage'], tone: 'academic', icon: 'grid' },
  { label: 'Subjects', description: 'Core and co-curricular subjects', route: 'subjects', permissions: ['subjects.manage'], tone: 'academic', icon: 'book' },
  { label: 'Class Subjects', description: 'Marks and subject assignment', route: 'class-subjects', permissions: ['class_subjects.manage'], tone: 'academic', icon: 'clipboard' },
  { label: 'Groups', description: 'Science and general tracks', route: 'student-groups', permissions: ['student_groups.manage'], tone: 'academic', icon: 'nodes' },
  { label: 'Shifts', description: 'Morning and day operations', route: 'shifts', permissions: ['shifts.manage'], tone: 'academic', icon: 'clock' },
  { label: 'Timetable', description: 'Periods, rooms, teachers', route: 'timetable', permissions: ['timetable.manage'], tone: 'academic', icon: 'schedule' },
  { label: 'Assignments', description: 'Class work and submissions', route: 'assignments', permissions: ['assignments.manage'], tone: 'academic', icon: 'edit' },
  { label: 'Students', description: 'Admissions and profiles', route: 'students', permissions: ['students.manage'], tone: 'people', icon: 'users' },
  { label: 'Enrollments', description: 'Year, class, roll, section', route: 'enrollments', permissions: ['enrollments.manage'], tone: 'people', icon: 'id' },
  { label: 'Teachers', description: 'Teaching profiles', route: 'teacher-profiles', permissions: ['teachers.manage'], tone: 'people', icon: 'teacher' },
  { label: 'Attendance', description: 'Student daily records', route: 'attendance', permissions: ['attendance.manage'], tone: 'people', icon: 'check-circle' },
  { label: 'Designations', description: 'Staff role catalog', route: 'designations', permissions: ['designations.manage'], tone: 'people', icon: 'badge' },
  { label: 'Employees', description: 'Staff records and salary base', route: 'employees', permissions: ['employees.manage'], tone: 'people', icon: 'briefcase' },
  { label: 'Exams', description: 'Schedules and publication', route: 'exams', permissions: ['exams.manage'], tone: 'operations', icon: 'clipboard' },
  { label: 'Marks', description: 'Entry and verification', route: 'marks', permissions: ['marks.enter.any', 'marks.enter.own'], tone: 'operations', icon: 'chart' },
  { label: 'Reports', description: 'Results, PDFs, attendance', route: 'reports', permissions: ['reports.view'], tone: 'operations', icon: 'report' },
  { label: 'Promotions', description: 'Year-end progression', route: 'promotions', permissions: ['promotions.manage'], tone: 'operations', icon: 'arrow-up' },
  { label: 'Calendar', description: 'Events and holidays', route: 'calendar', permissions: ['calendar.manage', 'reports.view'], tone: 'operations', icon: 'calendar' },
  { label: 'Documents', description: 'Circulars and files', route: 'documents', permissions: ['documents.manage'], tone: 'operations', icon: 'file' },
  { label: 'Notifications', description: 'Alerts and delivery queue', route: 'notifications', permissions: ['reports.view', 'student.portal.view', 'parent.portal.view'], tone: 'operations', icon: 'report' },
  { label: 'Invitations', description: 'Team access and onboarding', route: 'invitations', permissions: ['users.manage'], tone: 'operations', icon: 'id' },
  { label: 'Finance', description: 'Fees, invoices, payments', route: 'finance', permissions: ['finance.manage'], tone: 'finance', icon: 'wallet' },
  { label: 'Discounts', description: 'Policies and student waivers', route: 'discounts', permissions: ['finance.manage'], tone: 'finance', icon: 'wallet' },
  { label: 'Invoice Payments', description: 'Collections and receipts', route: 'invoice-payments', permissions: ['finance.manage'], tone: 'finance', icon: 'credit-card' },
  { label: 'Payment Gateways', description: 'bKash, Nagad, cards', route: 'payment-gateways', permissions: ['payment_gateways.manage'], tone: 'finance', icon: 'credit-card' },
  { label: 'Staff Ops', description: 'Payroll, attendance, leave', route: 'staff-operations', permissions: ['payroll.manage', 'employee_attendance.manage', 'leave.manage'], tone: 'finance', icon: 'settings' },
  { label: 'School Settings', description: 'Portal and reporting rules', route: 'settings', permissions: ['schools.manage'], tone: 'finance', icon: 'settings' },
  { label: 'Student Portal', description: 'Student-facing live view', route: 'portal-student', permissions: ['student.portal.view'], tone: 'finance', icon: 'users' },
  { label: 'Parent Portal', description: 'Guardian-facing live view', route: 'portal-parent', permissions: ['parent.portal.view'], tone: 'finance', icon: 'users' },
]

export const schoolWorkspaceGroups = [
  { title: 'Academics', tone: 'academic', icon: 'book' },
  { title: 'People', tone: 'people', icon: 'users' },
  { title: 'Operations', tone: 'operations', icon: 'chart' },
  { title: 'Finance', tone: 'finance', icon: 'wallet' },
] as const
