import type { ApiSchool } from '~/composables/useApi'

export type RoleDashboardKey = 'admin' | 'principal' | 'teacher' | 'accountant' | 'student' | 'parent' | 'auditor'

export type PermissionGuard = {
  permissions?: string[]
  roles?: string[]
  all?: boolean
}

export type GuardedNavItem = PermissionGuard & {
  heading?: string
  title?: string
  children?: GuardedNavItem[]
  [key: string]: unknown
}

export const roleDashboardProfiles: Record<RoleDashboardKey, {
  label: string
  title: string
  subtitle: string
  badge: string
  quickLinks: { label: string, to: string, permission?: string }[]
}> = {
  admin: {
    label: 'Admin',
    title: 'Admin command center',
    subtitle: 'Campus operations, people, finance, reports, and system governance in one place.',
    badge: 'Full operations',
    quickLinks: [
      { label: 'Manage schools', to: '/schools', permission: 'schools.manage' },
      { label: 'System admin', to: '/admin', permission: 'audit.view' },
      { label: 'Finance desk', to: '/finance/fees', permission: 'finance.manage' },
      { label: 'Reports', to: '/reports', permission: 'reports.view' },
    ],
  },
  principal: {
    label: 'Principal',
    title: 'Principal dashboard',
    subtitle: 'Academic readiness, attendance risk, exams, staff operations, and school priorities.',
    badge: 'Academic leadership',
    quickLinks: [
      { label: 'Attendance', to: '/attendance', permission: 'attendance.manage' },
      { label: 'Marks', to: '/marks', permission: 'marks.enter.any' },
      { label: 'Reports', to: '/reports', permission: 'reports.view' },
      { label: 'Classes', to: '/classes', permission: 'academic_classes.manage' },
    ],
  },
  teacher: {
    label: 'Teacher',
    title: 'Teacher dashboard',
    subtitle: 'Class attendance, assignments, exams, marks entry, and student follow-up.',
    badge: 'Classroom work',
    quickLinks: [
      { label: 'Mark attendance', to: '/attendance', permission: 'attendance.manage' },
      { label: 'Marks entry', to: '/marks', permission: 'marks.enter.own' },
      { label: 'Reports', to: '/reports', permission: 'reports.view' },
    ],
  },
  accountant: {
    label: 'Accountant',
    title: 'Finance dashboard',
    subtitle: 'Collections, invoices, salary movement, discounts, and payment gateway health.',
    badge: 'Finance operations',
    quickLinks: [
      { label: 'Fees and invoices', to: '/finance/fees', permission: 'finance.manage' },
      { label: 'Reports', to: '/reports', permission: 'reports.view' },
      { label: 'Students', to: '/students', permission: 'students.manage' },
    ],
  },
  student: {
    label: 'Student',
    title: 'Student dashboard',
    subtitle: 'Results, attendance, invoices, notifications, and student portal shortcuts.',
    badge: 'Student portal',
    quickLinks: [
      { label: 'My reports', to: '/reports', permission: 'reports.view' },
    ],
  },
  parent: {
    label: 'Parent',
    title: 'Parent dashboard',
    subtitle: 'Child attendance, results, invoices, notifications, and guardian follow-up.',
    badge: 'Guardian portal',
    quickLinks: [
      { label: 'Child reports', to: '/reports', permission: 'reports.view' },
    ],
  },
  auditor: {
    label: 'Auditor',
    title: 'Audit dashboard',
    subtitle: 'Read-only reporting, audit trail visibility, and operational evidence.',
    badge: 'Read-only review',
    quickLinks: [
      { label: 'Reports', to: '/reports', permission: 'reports.view' },
      { label: 'Audit area', to: '/admin', permission: 'audit.view' },
    ],
  },
}

export function schoolPermissions(school: ApiSchool | null | undefined): string[] {
  return school?.permissions ?? []
}

export function schoolRoles(school: ApiSchool | null | undefined): string[] {
  return school?.roles?.map(role => role.key) ?? []
}

export function hasAnyPermission(school: ApiSchool | null | undefined, permissions: string[] = []) {
  if (!permissions.length)
    return true

  const available = schoolPermissions(school)

  return permissions.some(permission => available.includes(permission))
}

export function canAccessGuard(school: ApiSchool | null | undefined, guard: PermissionGuard = {}) {
  if (guard.all)
    return true

  const roleMatch = guard.roles?.some(role => schoolRoles(school).includes(role)) ?? false
  const permissionMatch = hasAnyPermission(school, guard.permissions)

  if (guard.roles?.length && guard.permissions?.length)
    return roleMatch || permissionMatch

  if (guard.roles?.length)
    return roleMatch

  return permissionMatch
}

export function resolveRoleDashboard(school: ApiSchool | null | undefined): RoleDashboardKey {
  const roles = schoolRoles(school)
  const permissions = schoolPermissions(school)

  if (roles.some(role => ['super-admin', 'school-owner', 'school-admin'].includes(role)))
    return 'admin'
  if (roles.includes('principal'))
    return 'principal'
  if (roles.includes('accountant') || permissions.includes('finance.manage'))
    return 'accountant'
  if (roles.includes('teacher') || permissions.includes('marks.enter.own'))
    return 'teacher'
  if (roles.includes('student') || permissions.includes('student.portal.view'))
    return 'student'
  if (roles.includes('parent') || permissions.includes('parent.portal.view'))
    return 'parent'
  if (roles.includes('read-only-auditor') || permissions.includes('audit.view'))
    return 'auditor'

  return 'teacher'
}

export function dashboardPathForSchool(school: ApiSchool | null | undefined) {
  return `/dashboard/${resolveRoleDashboard(school)}`
}

function stripGuardFields(item: GuardedNavItem): GuardedNavItem {
  const { permissions: _permissions, roles: _roles, all: _all, children, ...rest } = item

  if (children)
    return { ...rest, children }

  return rest
}

export function filterGuardedNavItems(items: GuardedNavItem[], school: ApiSchool | null | undefined): GuardedNavItem[] {
  const visible = items
    .map((item) => {
      if (item.heading)
        return item

      if (item.children) {
        const children = filterGuardedNavItems(item.children, school)

        if (!children.length)
          return null

        return stripGuardFields({ ...item, children })
      }

      return canAccessGuard(school, item) ? stripGuardFields(item) : null
    })
    .filter((item): item is GuardedNavItem => Boolean(item))

  return visible.filter((item, index) => {
    if (!item.heading)
      return true

    return visible.slice(index + 1).some((nextItem) => {
      if (nextItem.heading)
        return false

      return true
    })
  })
}
