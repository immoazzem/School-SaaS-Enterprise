import { mkdir } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium } from 'playwright'

process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0'

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
const apiBase = process.env.QA_API_BASE || 'http://127.0.0.1:8010/api/v1'

const results = []
const qaEmail = process.env.QA_EMAIL || 'test@example.com'
const qaPassword = process.env.QA_PASSWORD || 'password'

function isIgnorableConsoleError(message) {
  return message.includes("Failed to load resource: the server responded with a status of 404 ()")
    || message.includes('Failed to load resource: the server responded with a status of 404 (Not Found)')
    || message.includes("[intlify] Not found 'Admin' key in 'en' locale messages.")
}

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
  await page.goto(`${baseURL}${path}`, { waitUntil: 'domcontentloaded' })
  await page.waitForLoadState('load')
  await page.waitForTimeout(600)
  await assertNoVisibleErrors(page, path)
}

async function login(page) {
  await page.goto(`${baseURL}/login`, { waitUntil: 'domcontentloaded' })
  await page.waitForLoadState('load')
  const emailField = page.locator('input[type="email"]').first()
  const passwordField = page.locator('input[type="password"]').first()
  const continueButton = page.getByRole('button', { name: /Enter workspace/i })
    .or(page.getByRole('button', { name: /Continue to Workspace/i }))
    .or(page.getByRole('button', { name: /^Continue$/i }))

  await emailField.waitFor({ timeout: 15000 })
  await emailField.fill(qaEmail)
  await passwordField.fill(qaPassword)
  await continueButton.click()
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

async function expectTextWithTimeout(page, text, timeout = 30000) {
  await page.getByText(text, { exact: false }).first().waitFor({ timeout })
}

async function saveScreenshot(page, fileName) {
  await mkdir(artifactDir, { recursive: true })
  await page.screenshot({ path: resolve(artifactDir, fileName), fullPage: true })
}

async function testMarks(page) {
  const gradeName = `QA Grade ${stamp}`
  const gradeCode = `QG${stamp.slice(-6)}`
  const marksValue = `74.${stamp.slice(-2)}`

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
  await fillLabel(page, 'Marks obtained', marksValue)
  await page.getByRole('button', { name: 'Save marks' }).click()
  await expectText(page, 'Marks entry saved.')
  const createdRow = page.locator('tbody tr', { hasText: `${marksValue} /` }).first()
  await createdRow.waitFor({ timeout: 15000 })
  await createdRow.getByRole('button', { name: 'Verify' }).click()
  await Promise.any([
    expectTextWithTimeout(page, 'Marks entry verified.', 30000),
    createdRow.getByText('verified', { exact: false }).waitFor({ timeout: 30000 }),
  ])

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
  await expectTextWithTimeout(page, 'promotion records executed', 30000)
  await page.getByText('completed', { exact: false }).first().waitFor({ timeout: 30000 })

  await page.getByRole('button', { name: /^Rollback$/i }).click()
  await expectTextWithTimeout(page, 'Promotion batch rolled back.', 30000)

  record('Promotions preview/create/execute/rollback', 'pass', `${fromYearId}->${toYearId}`)
}

async function preparePromotionsState() {
  const loginResponse = await fetch(`${apiBase}/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({
      email: qaEmail,
      password: qaPassword,
    }),
  })

  if (!loginResponse.ok) {
    throw new Error(`Unable to log in for promotion prep: ${loginResponse.status}`)
  }

  const loginPayload = await loginResponse.json()
  const token = loginPayload.token
  const headers = {
    Accept: 'application/json',
    Authorization: `Bearer ${token}`,
  }

  const batchesResponse = await fetch(`${apiBase}/schools/${schoolId}/promotions`, {
    headers,
  })

  if (!batchesResponse.ok) {
    throw new Error(`Unable to load promotion batches: ${batchesResponse.status}`)
  }

  const batchesPayload = await batchesResponse.json()
  const matchingBatch = batchesPayload.data.find(batch =>
    Number(batch.from_academic_year_id) === Number(fromYearId)
    && Number(batch.to_academic_year_id) === Number(toYearId)
    && Number(batch.from_academic_class_id) === Number(fromClassId)
    && Number(batch.to_academic_class_id) === Number(toClassId)
    && batch.status === 'completed'
  )

  if (!matchingBatch) {
    return
  }

  const rollbackResponse = await fetch(
    `${apiBase}/schools/${schoolId}/promotions/${matchingBatch.id}/rollback`,
    {
      method: 'POST',
      headers,
    },
  )

  if (!rollbackResponse.ok) {
    throw new Error(`Unable to rollback promotion batch ${matchingBatch.id}: ${rollbackResponse.status}`)
  }
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
    if (message.type() === 'error' && !isIgnorableConsoleError(message.text()))
      errors.push(message.text())
  })

  try {
    await preparePromotionsState()
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
