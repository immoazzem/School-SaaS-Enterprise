import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://localhost:3000'
const password = process.env.QA_PASSWORD || 'password'

const roles = [
  {
    email: 'superadmin@example.com',
    dashboard: '/dashboard/admin',
    title: 'Admin command center',
    visible: ['Dashboard', 'Finance', 'Admin', 'Schools'],
  },
  {
    email: 'farhana.kabir@example.com',
    dashboard: '/dashboard/principal',
    title: 'Principal dashboard',
    visible: ['Dashboard', 'Attendance', 'Marks', 'Classes', 'Reports'],
    hidden: ['Finance'],
  },
  {
    email: 'amina.rahman@example.com',
    dashboard: '/dashboard/teacher',
    title: 'Teacher dashboard',
    visible: ['Dashboard', 'Attendance', 'Marks', 'Reports'],
    hidden: ['Finance', 'Admin', 'Settings'],
  },
  {
    email: 'mahmud.alam@example.com',
    dashboard: '/dashboard/accountant',
    title: 'Finance dashboard',
    visible: ['Dashboard', 'Finance', 'Reports', 'Students'],
    hidden: ['Admin', 'Classes', 'Attendance'],
  },
  {
    email: 'student001@example.com',
    dashboard: '/dashboard/student',
    title: 'Student dashboard',
    visible: ['Dashboard', 'Reports'],
    hidden: ['Finance', 'Admin', 'Settings', 'Schools'],
  },
  {
    email: 'guardian001@example.com',
    dashboard: '/dashboard/parent',
    title: 'Parent dashboard',
    visible: ['Dashboard', 'Reports'],
    hidden: ['Finance', 'Admin', 'Settings', 'Schools'],
  },
  {
    email: 'auditor@example.com',
    dashboard: '/dashboard/auditor',
    title: 'Audit dashboard',
    visible: ['Dashboard', 'Reports', 'Admin'],
    hidden: ['Finance', 'Students', 'Attendance', 'Marks'],
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

async function assertRole(page, role) {
  await login(page, role.email)
  await page.waitForURL(url => url.pathname === role.dashboard, { timeout: 60000 })
  await page.getByRole('heading', { name: role.title }).waitFor({ state: 'visible', timeout: 30000 })

  const text = await navText(page)

  for (const item of role.visible) {
    if (!text.includes(item))
      throw new Error(`${role.email} should see nav item "${item}", nav was: ${text}`)
  }

  for (const item of role.hidden || []) {
    if (text.includes(item))
      throw new Error(`${role.email} should not see nav item "${item}", nav was: ${text}`)
  }

  await page.screenshot({
    path: `../../docs/browser-checks/role-dashboard-${role.dashboard.split('/').pop()}-${Date.now()}.png`,
    fullPage: true,
  })

  console.log(`PASS ${role.email} -> ${role.dashboard}`)
}

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } })

try {
  for (const role of roles)
    await assertRole(page, role)

  console.log(`Role dashboard access completed with ${roles.length} passed checks.`)
}
finally {
  await browser.close()
}
