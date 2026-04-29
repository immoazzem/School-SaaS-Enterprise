// School SaaS Enterprise — Vertical Navigation Menu

export default [
  { heading: 'Overview' },
  {
    title: 'Dashboard',
    icon: { icon: 'tabler-dashboard' },
    to: { name: 'dashboard' },
    all: true,
  },
  {
    title: 'Analytics',
    icon: { icon: 'tabler-chart-bar' },
    to: { name: 'analytics' },
    permissions: ['reports.view', 'audit.view'],
  },
  {
    title: 'Reports',
    icon: { icon: 'tabler-file-analytics' },
    to: { name: 'reports' },
    permissions: ['reports.view'],
  },

  { heading: 'Academics' },
  {
    title: 'Students',
    icon: { icon: 'tabler-users' },
    to: { name: 'students' },
    permissions: ['students.manage', 'student.portal.view', 'parent.portal.view'],
  },
  {
    title: 'Attendance',
    icon: { icon: 'tabler-user-check' },
    to: { name: 'attendance' },
    permissions: ['attendance.manage', 'student.portal.view', 'parent.portal.view'],
  },
  {
    title: 'Marks',
    icon: { icon: 'tabler-clipboard-check' },
    to: { name: 'marks' },
    permissions: ['marks.enter.any', 'marks.enter.own', 'student.portal.view', 'parent.portal.view'],
  },
  {
    title: 'Classes',
    icon: { icon: 'tabler-building-community' },
    to: { name: 'classes' },
    permissions: ['academic_classes.manage', 'sections.manage'],
  },

  { heading: 'Operations' },
  {
    title: 'Finance',
    icon: { icon: 'tabler-coin' },
    to: { name: 'finance-fees' },
    permissions: ['finance.manage'],
  },
  {
    title: 'Notices',
    icon: { icon: 'tabler-bell' },
    to: { name: 'notices' },
    permissions: ['calendar.manage', 'documents.manage', 'reports.view'],
  },
  {
    title: 'Schools',
    icon: { icon: 'tabler-building-school' },
    to: { name: 'schools' },
    permissions: ['schools.manage', 'users.manage'],
  },
  { heading: 'System' },
  {
    title: 'Settings',
    icon: { icon: 'tabler-settings' },
    to: { name: 'settings' },
    permissions: ['schools.manage'],
  },
  {
    title: 'Admin',
    icon: { icon: 'tabler-shield-lock' },
    to: { name: 'admin' },
    permissions: ['audit.view', 'users.manage'],
  },
]
