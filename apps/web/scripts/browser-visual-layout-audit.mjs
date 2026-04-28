import { mkdir, writeFile } from 'node:fs/promises'
import path from 'node:path'
import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://localhost:3000'
const qaEmail = process.env.QA_EMAIL || 'superadmin@example.com'
const qaPassword = process.env.QA_PASSWORD || 'password'
const schoolId = process.env.QA_SCHOOL_ID || '1'
const timestamp = new Date().toISOString().replace(/[-:]/g, '').replace(/\..+/, '')
const artifactDir = path.resolve(process.cwd(), '../../docs/browser-checks', `visual-layout-${timestamp}`)

const viewports = [
  { name: 'desktop', width: 1440, height: 1000 },
  { name: 'laptop', width: 1280, height: 900 },
  { name: 'tablet', width: 834, height: 1112 },
  { name: 'mobile', width: 390, height: 844 },
]

const routes = [
  { name: 'dashboard', path: '/dashboard' },
  { name: 'schools', path: '/schools' },
  { name: 'admin-index', path: '/admin' },
  { name: 'admin-schools', path: '/admin/schools' },
  { name: 'admin-users', path: '/admin/users' },
  { name: 'admin-jobs', path: '/admin/jobs' },
  { name: 'admin-audit-logs', path: '/admin/audit-logs' },
  { name: 'academic-years', path: `/schools/${schoolId}/academic-years` },
  { name: 'academic-classes', path: `/schools/${schoolId}/academic-classes` },
  { name: 'academic-sections', path: `/schools/${schoolId}/academic-sections` },
  { name: 'subjects', path: `/schools/${schoolId}/subjects` },
  { name: 'class-subjects', path: `/schools/${schoolId}/class-subjects` },
  { name: 'student-groups', path: `/schools/${schoolId}/student-groups` },
  { name: 'shifts', path: `/schools/${schoolId}/shifts` },
  { name: 'students', path: `/schools/${schoolId}/students` },
  { name: 'enrollments', path: `/schools/${schoolId}/enrollments` },
  { name: 'attendance', path: `/schools/${schoolId}/attendance` },
  { name: 'employees', path: `/schools/${schoolId}/employees` },
  { name: 'designations', path: `/schools/${schoolId}/designations` },
  { name: 'teacher-profiles', path: `/schools/${schoolId}/teacher-profiles` },
  { name: 'staff-operations', path: `/schools/${schoolId}/staff-operations` },
  { name: 'exams', path: `/schools/${schoolId}/exams` },
  { name: 'marks', path: `/schools/${schoolId}/marks` },
  { name: 'reports', path: `/schools/${schoolId}/reports` },
  { name: 'finance', path: `/schools/${schoolId}/finance` },
  { name: 'invoice-payments', path: `/schools/${schoolId}/invoice-payments` },
  { name: 'discounts', path: `/schools/${schoolId}/discounts` },
  { name: 'payment-gateways', path: `/schools/${schoolId}/payment-gateways` },
  { name: 'assignments', path: `/schools/${schoolId}/assignments` },
  { name: 'timetable', path: `/schools/${schoolId}/timetable` },
  { name: 'calendar', path: `/schools/${schoolId}/calendar` },
  { name: 'documents', path: `/schools/${schoolId}/documents` },
  { name: 'notifications', path: `/schools/${schoolId}/notifications` },
  { name: 'promotions', path: `/schools/${schoolId}/promotions` },
  { name: 'invitations', path: `/schools/${schoolId}/invitations` },
  { name: 'settings', path: `/schools/${schoolId}/settings` },
  { name: 'student-portal', path: `/schools/${schoolId}/portal-student` },
  { name: 'parent-portal', path: `/schools/${schoolId}/portal-parent` },
]

async function login(page) {
  await page.goto(`${baseURL}/login`, { waitUntil: 'domcontentloaded' })
  await page.waitForLoadState('networkidle', { timeout: 60000 }).catch(() => page.waitForLoadState('load'))

  const emailField = page.locator('input[type="email"]').first()
  const passwordField = page.locator('input[type="password"]').first()
  const submitButton = page.locator('form button[type="submit"]').first()

  await emailField.waitFor({ timeout: 60000 })
  await emailField.fill(qaEmail)
  await passwordField.fill(qaPassword)
  await submitButton.click()
  await page.waitForFunction(() => !window.location.pathname.startsWith('/login'), { timeout: 60000 })
}

async function collectLayoutIssues(page) {
  return await page.evaluate(() => {
    const viewportWidth = document.documentElement.clientWidth
    const viewportHeight = document.documentElement.clientHeight
    const bodyWidth = document.body.scrollWidth
    const docWidth = document.documentElement.scrollWidth
    const issues = []

    const allowedOverflow = new Set(['HTML', 'BODY'])
    const horizontalOverflow = Math.max(bodyWidth, docWidth) - viewportWidth

    if (horizontalOverflow > 4) {
      issues.push({
        type: 'horizontal-overflow',
        message: `Document is ${horizontalOverflow}px wider than viewport`,
        scrollWidth: Math.max(bodyWidth, docWidth),
        viewportWidth,
      })
    }

    const selectors = [
      '.workspace-stage',
      '.workspace-topbar',
      '.school-page-header',
      '.v-card',
      '.v-sheet',
      '.v-table',
      '.v-data-table',
      '.v-row',
      'form',
    ]

    const elements = [...document.querySelectorAll(selectors.join(','))]

    for (const element of elements) {
      if (!(element instanceof HTMLElement) || allowedOverflow.has(element.tagName))
        continue

      const rect = element.getBoundingClientRect()
      const style = window.getComputedStyle(element)
      const hidden = rect.width === 0 || rect.height === 0 || style.visibility === 'hidden' || style.display === 'none'

      if (hidden)
        continue

      if (rect.left < -4 || rect.right > viewportWidth + 4) {
        issues.push({
          type: 'element-overflow-x',
          selector: element.className || element.tagName.toLowerCase(),
          left: Math.round(rect.left),
          right: Math.round(rect.right),
          width: Math.round(rect.width),
          viewportWidth,
          text: element.innerText?.trim().slice(0, 100) || '',
        })
      }

      if (rect.top < -80) {
        issues.push({
          type: 'element-position-y',
          selector: element.className || element.tagName.toLowerCase(),
          top: Math.round(rect.top),
          height: Math.round(rect.height),
          viewportHeight,
        })
      }
    }

    const desktopDrawer = document.querySelector('.workspace-admin-drawer')
    const stage = document.querySelector('.workspace-stage')

    if (desktopDrawer instanceof HTMLElement && stage instanceof HTMLElement && viewportWidth >= 960) {
      const drawerRect = desktopDrawer.getBoundingClientRect()
      const stageRect = stage.getBoundingClientRect()

      if (stageRect.left < drawerRect.right - 2) {
        issues.push({
          type: 'drawer-overlap',
          message: 'Workspace content starts underneath the left navigation drawer',
          drawerRight: Math.round(drawerRect.right),
          stageLeft: Math.round(stageRect.left),
        })
      }
    }

    const visibleErrorText = [...document.querySelectorAll('body *')]
      .filter((element) => {
        const rect = element.getBoundingClientRect()
        const style = window.getComputedStyle(element)

        return rect.width > 0 && rect.height > 0 && style.visibility !== 'hidden' && style.display !== 'none'
      })
      .map(element => element.textContent?.trim() || '')
      .find(text => /\b(cannot read|undefined is not|not found|unable to load|runtime error|server error|\[GET\].*404)\b/i.test(text))

    if (visibleErrorText) {
      issues.push({
        type: 'visible-error-copy',
        message: visibleErrorText.slice(0, 180),
      })
    }

    return {
      url: window.location.href,
      title: document.title,
      viewportWidth,
      viewportHeight,
      scrollWidth: Math.max(bodyWidth, docWidth),
      issues,
    }
  })
}

await mkdir(artifactDir, { recursive: true })

const browser = await chromium.launch({ headless: true })
const allResults = []

try {
  for (const viewport of viewports) {
    const context = await browser.newContext({ viewport })
    const page = await context.newPage()
    page.setDefaultTimeout(60000)

    await login(page)

    for (const route of routes) {
      await page.goto(`${baseURL}${route.path}`, { waitUntil: 'domcontentloaded' })
      await page.waitForLoadState('networkidle', { timeout: 60000 }).catch(() => page.waitForLoadState('load'))
      await page.waitForTimeout(900)

      const result = {
        route: route.name,
        path: route.path,
        viewport: viewport.name,
        ...(await collectLayoutIssues(page)),
      }

      if (result.issues.length) {
        const screenshotName = `${viewport.name}-${route.name}.png`

        await page.screenshot({ path: path.join(artifactDir, screenshotName), fullPage: true })
        result.screenshot = screenshotName
      }

      allResults.push(result)
    }

    await context.close()
  }
}
finally {
  await browser.close()
}

const failures = allResults.filter(result => result.issues.length)
const report = {
  generatedAt: new Date().toISOString(),
  baseURL,
  routeCount: routes.length,
  viewportCount: viewports.length,
  failures: failures.length,
  results: allResults,
}

await writeFile(path.join(artifactDir, 'report.json'), `${JSON.stringify(report, null, 2)}\n`)

if (failures.length) {
  console.error(`Visual layout audit found ${failures.length} route/viewport failures.`)

  for (const failure of failures.slice(0, 40)) {
    console.error(`- ${failure.viewport} ${failure.path}: ${failure.issues.map(issue => issue.type).join(', ')}`)
  }

  console.error(`Report: ${path.join(artifactDir, 'report.json')}`)
  process.exit(1)
}

console.log(`Visual layout audit passed: ${routes.length} routes across ${viewports.length} viewports.`)
console.log(`Report: ${path.join(artifactDir, 'report.json')}`)
