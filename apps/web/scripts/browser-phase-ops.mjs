import { mkdir } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://localhost:3000'
const schoolId = process.env.QA_SCHOOL_ID || '1'
const fromYearId = process.env.QA_FROM_YEAR_ID || '10'
const toYearId = process.env.QA_TO_YEAR_ID || '11'
const fromClassId = process.env.QA_FROM_CLASS_ID || '5'
const toClassId = process.env.QA_TO_CLASS_ID || '1'
const stamp = process.env.QA_STAMP || new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14)
const scriptDir = dirname(fileURLToPath(import.meta.url))
const repoRoot = resolve(scriptDir, '../../..')
const artifactDir = resolve(repoRoot, 'docs/browser-checks')

const results = []

function record(name, status, detail = '') {
  const line = `${status === 'pass' ? 'PASS' : 'FAIL'} ${name}${detail ? ` - ${detail}` : ''}`
  results.push({ name, status, detail })
  console.log(line)
}

async function assertNoVisibleErrors(page, name) {
  const body = await page.locator('body').innerText()
  const needles = [
    'Unable to load',
    'Cannot read properties',
    'Something went wrong',
    'Internal Server Error',
    'Page not found',
    'NetworkError',
  ]
  const found = needles.find(needle => body.includes(needle))

  if (found) {
    throw new Error(`${name}: visible error text found: ${found}`)
  }
}

async function goto(page, path) {
  await page.goto(`${baseURL}${path}`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(600)
  await assertNoVisibleErrors(page, path)
}

async function login(page) {
  await page.goto(baseURL, { waitUntil: 'networkidle' })
  await page.getByLabel('Work email').fill('test@example.com')
  await page.getByLabel('Password').fill('password')
  await page.getByRole('button', { name: /Enter workspace/i }).click()
  await page.waitForURL(url => !url.pathname.startsWith('/login'), { timeout: 20000 })
  await assertNoVisibleErrors(page, 'login')
}

async function fillLabel(page, label, value, index = 0) {
  await page.getByLabel(label, { exact: true }).nth(index).fill(String(value))
}

async function selectLabel(page, label, valueOrLabel, index = 0) {
  const field = page.getByLabel(label, { exact: true }).nth(index)

  try {
    await field.selectOption({ label: String(valueOrLabel) })
    return
  }
  catch {}

  try {
    await field.selectOption(String(valueOrLabel))
    return
  }
  catch {}

  const matchedValue = await field.evaluate((select, expected) => {
    const normalized = String(expected).toLowerCase()
    const option = Array.from(select.options).find((item) => {
      if (!item.value)
        return false

      return item.textContent?.trim().toLowerCase().includes(normalized)
    })

    return option?.value ?? null
  }, String(valueOrLabel))

  if (!matchedValue) {
    throw new Error(`Option not found for ${label}: ${valueOrLabel}`)
  }

  await field.selectOption(matchedValue)
}

async function selectFirstNonEmptyLabel(page, label, index = 0) {
  const field = page.getByLabel(label, { exact: true }).nth(index)

  await page.waitForFunction((element) => {
    if (!(element instanceof HTMLSelectElement))
      return false

    return Array.from(element.options).some(option => option.value)
  }, await field.elementHandle(), { timeout: 15000 })

  const matchedValue = await field.evaluate((select) => {
    const option = Array.from(select.options).find(item => item.value)

    return option?.value ?? null
  })

  if (!matchedValue) {
    throw new Error(`No selectable option found for ${label}`)
  }

  await field.selectOption(matchedValue)
}

async function expectText(page, text) {
  await page.getByText(text, { exact: false }).first().waitFor({ timeout: 15000 })
}

async function saveScreenshot(page, fileName) {
  await mkdir(artifactDir, { recursive: true })
  await page.screenshot({ path: resolve(artifactDir, fileName), fullPage: true })
}

async function testMarks(page) {
  const gradeName = `QA Grade ${stamp}`
  const gradeCode = `QG${stamp.slice(-6)}`

  await goto(page, `/schools/${schoolId}/marks`)
  await fillLabel(page, 'Name', gradeName)
  await fillLabel(page, 'Code', gradeCode)
  await fillLabel(page, 'Min percent', '88')
  await fillLabel(page, 'Max percent', '100')
  await fillLabel(page, 'Grade point', '5')
  await fillLabel(page, 'Fail below', '33')
  await page.getByRole('button', { name: 'Save grade scale' }).click()
  await expectText(page, 'Grade scale saved.')
  await expectText(page, gradeCode)

  await selectFirstNonEmptyLabel(page, 'Exam')
  await selectFirstNonEmptyLabel(page, 'Class subject')
  await selectFirstNonEmptyLabel(page, 'Student')
  await fillLabel(page, 'Marks obtained', '74')
  await page.getByRole('button', { name: 'Save marks' }).click()
  await expectText(page, 'Marks entry saved.')
  await page.getByRole('button', { name: 'Verify' }).first().click()
  await expectText(page, 'Marks entry verified.')

  record('Marks workspace create/verify', 'pass', gradeCode)
}

async function testReports(page) {
  await goto(page, `/schools/${schoolId}/reports`)
  await selectFirstNonEmptyLabel(page, 'Exam', 0)
  await page.getByRole('button', { name: 'Publish results' }).click()
  await expectText(page, 'published')

  await selectFirstNonEmptyLabel(page, 'Exam', 1)
  await selectFirstNonEmptyLabel(page, 'Student')
  await page.getByRole('button', { name: 'Queue marksheet' }).click()
  await expectText(page, 'Report queued:')

  await page.getByRole('button', { name: 'Result sheet' }).click()
  await expectText(page, 'Report queued:')

  await page.getByRole('button', { name: 'ID card' }).click()
  await expectText(page, 'Report queued:')

  await page.getByRole('button', { name: 'Check file' }).click()
  await expectText(page, 'Report')

  record('Reports workspace publish/queue/check', 'pass')
}

async function testPromotions(page) {
  await goto(page, `/schools/${schoolId}/promotions`)
  await selectLabel(page, 'From year', fromYearId)
  await selectLabel(page, 'To year', toYearId)
  await selectLabel(page, 'From class', fromClassId)
  await selectLabel(page, 'To class', toClassId)
  await page.getByRole('button', { name: /Preview students/i }).click()
  await expectText(page, 'ready for review')

  await page.getByRole('button', { name: /Create draft batch/i }).click()
  await expectText(page, 'Draft batch')

  await page.getByRole('button', { name: /^Execute$/i }).click()
  await expectText(page, 'promotion records executed')

  await page.getByRole('button', { name: /^Rollback$/i }).click()
  await expectText(page, 'Promotion batch rolled back.')

  record('Promotions preview/create/execute/rollback', 'pass', `${fromYearId}->${toYearId}`)
}

async function testNotifications(page) {
  await goto(page, `/schools/${schoolId}/notifications`)
  await expectText(page, 'Notifications')
  await expectText(page, 'Notification register')
  record('Notifications workspace load', 'pass')
}

async function testStudentPortal(page) {
  await goto(page, `/schools/${schoolId}/portal-student`)
  await expectText(page, 'Student portal view')
  await expectText(page, 'Profile')
  await expectText(page, 'Notifications')
  record('Student portal load', 'pass')
}

async function testParentPortal(page) {
  await goto(page, `/schools/${schoolId}/portal-parent`)
  await expectText(page, 'Parent portal view')
  await expectText(page, 'Selected child')
  await expectText(page, 'Guardian notifications')
  record('Parent portal load', 'pass')
}

async function main() {
  const browser = await chromium.launch({ headless: true })
  const page = await browser.newPage({ viewport: { width: 1440, height: 1080 } })
  const errors = []

  page.on('console', (message) => {
    if (message.type() === 'error')
      errors.push(message.text())
  })

  try {
    await login(page)
    await testMarks(page)
    await testReports(page)
    await testPromotions(page)
    await testNotifications(page)
    await testStudentPortal(page)
    await testParentPortal(page)

    await saveScreenshot(page, `phase-ops-suite-${stamp}.png`)

    if (errors.length) {
      throw new Error(`Console errors detected: ${errors.join(' | ')}`)
    }

    console.log(JSON.stringify(results, null, 2))
  }
  finally {
    await browser.close()
  }
}

main().catch((error) => {
  console.error(error)
  process.exitCode = 1
})
