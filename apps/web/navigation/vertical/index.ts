// School SaaS Enterprise — Vertical Navigation Menu

export default [
  { heading: 'Overview' },
  {
    title: 'Dashboard',
    icon: { icon: 'tabler-dashboard' },
    to: { name: 'index' },
  },
  {
    title: 'Analytics',
    icon: { icon: 'tabler-chart-bar' },
    to: { name: 'analytics' },
  },
  {
    title: 'Reports',
    icon: { icon: 'tabler-file-analytics' },
    to: { name: 'reports' },
  },

  { heading: 'Academics' },
  {
    title: 'Students',
    icon: { icon: 'tabler-users' },
    to: { name: 'students' },
  },
  {
    title: 'Attendance',
    icon: { icon: 'tabler-user-check' },
    to: { name: 'attendance' },
  },
  {
    title: 'Marks',
    icon: { icon: 'tabler-clipboard-check' },
    to: { name: 'marks' },
  },
  {
    title: 'Classes',
    icon: { icon: 'tabler-building-community' },
    to: { name: 'classes' },
  },

  { heading: 'Operations' },
  {
    title: 'Finance',
    icon: { icon: 'tabler-coin' },
    to: { name: 'finance-fees' },
  },
  {
    title: 'Notices',
    icon: { icon: 'tabler-bell' },
    to: { name: 'notices' },
  },
  {
    title: 'Schools',
    icon: { icon: 'tabler-building-school' },
    to: { name: 'schools' },
  },
  { heading: 'System' },
  {
    title: 'Settings',
    icon: { icon: 'tabler-settings' },
    to: { name: 'settings' },
  },
  {
    title: 'Admin',
    icon: { icon: 'tabler-shield-lock' },
    to: { name: 'admin' },
  },
]
