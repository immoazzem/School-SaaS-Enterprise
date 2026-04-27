import { mkdir } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://localhost:3000'
const schoolId = process.env.QA_SCHOOL_ID || '1'
const qaEmail = process.env.QA_EMAIL || 'superadmin@example.com'
const qaPassword = process.env.QA_PASSWORD || 'password'
const stamp = new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14)
const scriptDir = dirname(fileURLToPath(import.meta.url))
const repoRoot = resolve(scriptDir, '../../..')
const artifactDir = resolve(repoRoot, 'docs/browser-checks')

async function saveScreenshot(page, fileName) {
  await mkdir(artifactDir, { recursive: true })
  await page.screenshot({ path: resolve(artifactDir, fileName), fullPage: true })
}

async function login(page) {
  await page.goto(`${baseURL}/login`, { waitUntil: 'domcontentloaded' })
  await page.locator('input[type="email"]').first().waitFor({ timeout: 60000 })
  await page.locator('input[type="email"]').first().fill(qaEmail)
  await page.locator('input[type="password"]').first().fill(qaPassword)
  await page.getByRole('button', { name: /Enter workspace/i }).click()
  await page.waitForURL(url => !url.pathname.startsWith('/login'), { timeout: 20000 })
}

async function main() {
  const browser = await chromium.launch({ headless: true })
  const page = await browser.newPage({ viewport: { width: 1440, height: 1080 } })

  try {
    await login(page)
    await page.goto(`${baseURL}/schools/${schoolId}/attendance`, { waitUntil: 'domcontentloaded' })
    await page.waitForLoadState('load')

    await page.evaluate((schoolIdValue) => {
      const now = new Date().toISOString()
      localStorage.setItem('school-saas:offline-queue:attendance', JSON.stringify([
        {
          id: 'qa-auth-required',
          label: 'Attendance / QA Auth Required / 2026-04-20',
          schoolId: Number(schoolIdValue),
          method: 'POST',
          path: `/schools/${schoolIdValue}/student-attendance-records`,
          payload: {
            student_enrollment_id: 1,
            attendance_date: '2026-04-20',
            status: 'present',
            remarks: 'QA auth recovery check',
          },
          status: 'auth_required',
          attempts: 1,
          errorMessage: '401 Unauthenticated.',
          createdAt: now,
          updatedAt: now,
          lastAttemptAt: now,
        },
        {
          id: 'qa-conflict',
          label: 'Attendance / QA Conflict / 2026-04-21',
          schoolId: Number(schoolIdValue),
          method: 'POST',
          path: `/schools/${schoolIdValue}/student-attendance-records`,
          payload: {
            student_enrollment_id: 1,
            attendance_date: '2026-04-21',
            status: 'late',
            remarks: 'QA conflict review check',
          },
          status: 'conflict',
          attempts: 2,
          errorMessage: '422 Attendance already exists for this date.',
          createdAt: now,
          updatedAt: now,
          lastAttemptAt: now,
        },
      ]))
    }, schoolId)

    await page.reload({ waitUntil: 'domcontentloaded' })
    await page.getByText('Sign in required', { exact: false }).first().waitFor({ timeout: 15000 })
    await page.getByText('Needs review', { exact: false }).first().waitFor({ timeout: 15000 })
    await page.getByText('Review local payload', { exact: false }).first().click()
    await page.getByText('student_enrollment_id', { exact: false }).first().waitFor({ timeout: 15000 })

    await page.getByRole('button', { name: /^Retry$/i }).first().click()
    await page.getByText('Attendance queue item is ready to retry.', { exact: false }).first().waitFor({ timeout: 15000 })
    await page.getByText('1 ready', { exact: false }).first().waitFor({ timeout: 15000 })

    await saveScreenshot(page, `offline-queue-recovery-${stamp}.png`)

    await page.getByRole('button', { name: /Sign in again/i }).click()
    await page.waitForURL(url => url.pathname === '/login' && url.searchParams.get('redirect') === `/schools/${schoolId}/attendance`, { timeout: 20000 })

    console.log('PASS offline queue recovery')
  } finally {
    await browser.close()
  }
}

main().catch((error) => {
  console.error(error)
  process.exitCode = 1
})
