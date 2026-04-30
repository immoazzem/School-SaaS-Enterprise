import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://localhost:3000'
const password = process.env.QA_PASSWORD || 'password'
const schoolId = process.env.QA_SCHOOL_ID || '1'

const roles = [
  {
    email: 'superadmin@example.com',
    dashboard: '/dashboard/admin',
    title: 'Admin command center',
    visible: ['Dashboard', 'Finance', 'Admin', 'Schools'],
    schoolVisible: ['Dashboard', 'Students', 'Finance', 'Reports', 'School Settings'],
  },
  {
    email: 'farhana.kabir@example.com',
    dashboard: '/dashboard/principal',
    title: 'Principal dashboard',
    visible: ['Dashboard', 'Attendance', 'Marks', 'Classes', 'Reports'],
    hidden: ['Finance'],
    schoolVisible: ['Dashboard', 'Classes', 'Students', 'Attendance', 'Marks', 'Reports'],
    schoolHidden: ['Finance', 'Payment Gateways', 'School Settings', 'Invitations'],
  },
  {
    email: 'amina.rahman@example.com',
    dashboard: '/dashboard/teacher',
    title: 'Teacher dashboard',
    visible: ['Dashboard', 'Attendance', 'Marks', 'Reports'],
    hidden: ['Finance', 'Admin', 'Settings'],
    schoolVisible: ['Dashboard', 'Students', 'Attendance', 'Marks', 'Assignments', 'Reports'],
    schoolHidden: ['Finance', 'Employees', 'School Settings', 'Invitations'],
  },
  {
    email: 'mahmud.alam@example.com',
    dashboard: '/dashboard/accountant',
    title: 'Finance dashboard',
    visible: ['Dashboard', 'Finance', 'Reports', 'Students'],
    hidden: ['Admin', 'Classes', 'Attendance'],
    schoolVisible: ['Dashboard', 'Students', 'Finance', 'Invoice Payments', 'Payment Gateways', 'Staff Ops', 'Reports'],
    schoolHidden: ['Attendance', 'Marks', 'School Settings', 'Invitations'],
  },
  {
    email: 'student001@example.com',
    dashboard: '/dashboard/student',
    title: 'Student dashboard',
    visible: ['Dashboard', 'Reports'],
    hidden: ['Finance', 'Admin', 'Settings', 'Schools'],
    schoolVisible: ['Dashboard', 'Reports', 'Notifications', 'Student Portal'],
    schoolHidden: ['Finance', 'Students', 'Attendance', 'School Settings', 'Parent Portal'],
  },
  {
    email: 'guardian001@example.com',
    dashboard: '/dashboard/parent',
    title: 'Parent dashboard',
    visible: ['Dashboard', 'Reports'],
    hidden: ['Finance', 'Admin', 'Settings', 'Schools'],
    schoolVisible: ['Dashboard', 'Reports', 'Notifications', 'Parent Portal'],
    schoolHidden: ['Finance', 'Students', 'Attendance', 'School Settings', 'Student Portal'],
  },
  {
    email: 'auditor@example.com',
    dashboard: '/dashboard/auditor',
    title: 'Audit dashboard',
    visible: ['Dashboard', 'Reports', 'Admin'],
    hidden: ['Finance', 'Students', 'Attendance', 'Marks'],
    schoolVisible: ['Dashboard', 'Reports', 'Notifications'],
    schoolHidden: ['Finance', 'Students', 'Attendance', 'Marks', 'School Settings'],
  },
]

function navLocator(page) {
  return page.locator('.layout-vertical-nav, .v-navigation-drawer, aside').first()
}

async function login(page, email) {
  await page.context().clearCookies()
  await page.goto(`${baseURL}/login`, { waitUntil: 'domcontentloaded', timeout: 90000 })
  await page.evaluate(() => {
    localStorage.removeItem('school_saas_token')
    localStorage.removeItem('school_saas_school_id')
  })
  await page.goto(`${baseURL}/login`, { waitUntil: 'domcontentloaded', timeout: 90000 })

  const emailField = page.locator('input[type="email"]').first()
  await emailField.waitFor({ state: 'visible', timeout: 90000 })
  await emailField.fill(email)
  await page.locator('input[type="password"]').first().fill(password)
  await page.getByRole('button', { name: /Enter workspace/i }).click()
}

async function navText(page) {
  const nav = navLocator(page)
  await nav.waitFor({ state: 'visible', timeout: 30000 })

  return (await nav.innerText()).replace(/\s+/g, ' ')
}

async function assertTextIncludes(text, items, message) {
  for (const item of items || []) {
    if (!text.includes(item))
      throw new Error(`${message} should include "${item}", nav was: ${text}`)
  }
}

async function assertTextExcludes(text, items, message) {
  for (const item of items || []) {
    if (text.includes(item))
      throw new Error(`${message} should not include "${item}", nav was: ${text}`)
  }
}

async function assertRole(page, role) {
  await login(page, role.email)
  await page.waitForURL(url => url.pathname === role.dashboard, { timeout: 60000 })
  await page.getByRole('heading', { name: role.title }).waitFor({ state: 'visible', timeout: 30000 })

  const text = await navText(page)

  await assertTextIncludes(text, role.visible, `${role.email} global dashboard nav`)
  await assertTextExcludes(text, role.hidden, `${role.email} global dashboard nav`)

  await page.screenshot({
    path: `../../docs/browser-checks/role-dashboard-${role.dashboard.split('/').pop()}-${Date.now()}.png`,
    fullPage: true,
  })

  await page.goto(`${baseURL}/schools/${schoolId}`, { waitUntil: 'domcontentloaded', timeout: 90000 })
  await page.getByText(/Role-aware launchpad/i).waitFor({ state: 'visible', timeout: 60000 })

  const nestedGlobalShell = await page.locator('.layout-vertical-nav, .signal-navbar, .layout-navbar, .layout-footer').count()

  if (nestedGlobalShell > 0)
    throw new Error(`${role.email} school workspace is still nested inside the global dashboard shell.`)

  const schoolNavText = await navText(page)

  await assertTextIncludes(schoolNavText, role.schoolVisible, `${role.email} school workspace nav`)
  await assertTextExcludes(schoolNavText, role.schoolHidden, `${role.email} school workspace nav`)

  await page.screenshot({
    path: `../../docs/browser-checks/role-school-workspace-${role.dashboard.split('/').pop()}-${Date.now()}.png`,
    fullPage: true,
  })

  console.log(`PASS ${role.email} -> ${role.dashboard}`)
}

const browser = await chromium.launch({ headless: true })

try {
  for (const role of roles) {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } })
    const page = await context.newPage()

    try {
      await assertRole(page, role)
    }
    finally {
      await context.close()
    }
  }

  console.log(`Role dashboard and school workspace access completed with ${roles.length} passed checks.`)
}
finally {
  await browser.close()
}
