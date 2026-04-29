export default [
  {
    title: 'Dashboard',
    to: { name: 'dashboard' },
    icon: { icon: 'tabler-smart-home' },
    all: true,
  },
  {
    title: 'Reports',
    to: { name: 'reports' },
    icon: { icon: 'tabler-file-analytics' },
    permissions: ['reports.view'],
  },
]
