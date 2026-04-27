import { mkdir, writeFile } from 'node:fs/promises'
import { tmpdir } from 'node:os'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://localhost:3000'
const apiBase = process.env.QA_API_BASE || 'http://127.0.0.1:8010/api/v1'
const schoolId = process.env.QA_SCHOOL_ID || '1'
const qaEmail = process.env.QA_EMAIL || 'superadmin@example.com'
const qaPassword = process.env.QA_PASSWORD || 'password'
const stamp = process.env.QA_STAMP || new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14)
const uniqueId = stamp.slice(-6)
const scriptDir = dirname(fileURLToPath(import.meta.url))
const repoRoot = resolve(scriptDir, '../../..')
const artifactDir = resolve(repoRoot, 'docs/browser-checks')
const fixturePath = resolve(tmpdir(), `school-saas-document-fixture-${stamp}.txt`)

const results = []

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

async function saveScreenshot(page, fileName) {
  await mkdir(artifactDir, { recursive: true })
  await page.screenshot({ path: resolve(artifactDir, fileName), fullPage: true })
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

  if (found)
    throw new Error(`${name}: visible error text found: ${found}`)
}

async function goto(page, path) {
  await page.goto(`${baseURL}${path}`, { waitUntil: 'domcontentloaded' })
  await page.waitForLoadState('load')
  await page.waitForTimeout(700)
  await assertNoVisibleErrors(page, path)
}

async function login(page) {
  await goto(page, '/login')
  const emailField = page.locator('input[type="email"]').first()
  const passwordField = page.locator('input[type="password"]').first()
  await emailField.waitFor({ timeout: 15000 })
  await emailField.fill(qaEmail)
  await passwordField.fill(qaPassword)
  await page.getByRole('button', { name: /Enter workspace/i })
    .or(page.getByRole('button', { name: /Continue to Workspace/i }))
    .or(page.getByRole('button', { name: /^Continue$/i }))
    .click()
  await page.waitForURL(url => !url.pathname.startsWith('/login'), { timeout: 20000 })
}

async function expectText(page, text, timeout = 15000) {
  await page.getByText(text, { exact: false }).first().waitFor({ timeout })
}

async function selectFirstNonEmpty(page, selector, name) {
  const field = page.locator(selector)

  await page.waitForFunction((element) => {
    if (!(element instanceof HTMLSelectElement))
      return false

    return Array.from(element.options).some(option => option.value)
  }, await field.elementHandle(), { timeout: 15000 })

  const value = await field.evaluate((select) => {
    const option = Array.from(select.options).find(item => item.value)

    return option?.value ?? null
  })

  if (!value)
    throw new Error(`No selectable option found for ${name}`)

  await field.selectOption(value)
  return value
}

async function apiLogin() {
  const response = await fetch(`${apiBase}/auth/login`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email: qaEmail, password: qaPassword }),
  })

  if (!response.ok)
    throw new Error(`API login failed: ${response.status}`)

  const payload = await response.json()
  return payload.token
}

async function apiRequest(token, path, options = {}) {
  const response = await fetch(`${apiBase}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
  })

  if (!response.ok && response.status !== 204) {
    const body = await response.text()
    throw new Error(`API ${options.method || 'GET'} ${path} failed: ${response.status} ${body}`)
  }

  if (response.status === 204)
    return null

  return await response.json()
}

async function prepareState() {
  const token = await apiLogin()
  const gatewayPayload = await apiRequest(token, `/schools/${schoolId}/payment-gateway-configs?per_page=100`)
  const stripeConfig = gatewayPayload.data.find(config => config.gateway === 'stripe')

  if (stripeConfig) {
    await apiRequest(token, `/schools/${schoolId}/payment-gateway-configs/${stripeConfig.id}`, {
      method: 'DELETE',
    })
  }

  return token
}

async function testPaymentGateways(page) {
  await goto(page, `/schools/${schoolId}/payment-gateways`)
  await page.locator('#gateway-name').selectOption('stripe')
  await page.locator('#merchant-id').fill(`merchant-${uniqueId}`)
  await page.locator('#public-key').fill(`pk_test_${uniqueId}`)
  await page.locator('#secret-key').fill(`sk_test_${uniqueId}`)
  await page.getByRole('button', { name: /Save config/i }).click()
  await expectText(page, 'Gateway config saved.')

  const row = page.locator('.gateway-row', { hasText: 'Stripe' }).first()
  await row.waitFor({ timeout: 15000 })
  await row.getByRole('button', { name: /Edit/i }).click()
  await page.locator('#secret-key').fill(`sk_rotated_${uniqueId}`)
  await page.getByRole('button', { name: /Update config/i }).click()
  await expectText(page, 'Gateway config updated.')

  record('Payment gateway create/update', 'pass', 'Stripe')
}

async function testDocuments(page) {
  const title = `QA Document ${uniqueId}`
  await mkdir(artifactDir, { recursive: true })
  await writeFile(fixturePath, `QA document fixture ${stamp}\n`, 'utf8')

  await goto(page, `/schools/${schoolId}/documents`)
  await page.locator('#document-title').fill(title)
  await page.locator('#document-category').selectOption('circular')
  await page.locator('#document-file').setInputFiles(fixturePath)
  await page.getByRole('button', { name: /Upload document/i }).click()
  await expectText(page, 'Document uploaded.')

  const row = page.locator('tbody tr', { hasText: title }).first()
  await row.waitFor({ timeout: 15000 })
  await row.getByRole('button', { name: /Get link/i }).click()
  await expectText(page, 'Open signed file')

  record('Document upload/link', 'pass', title)
}

async function testAssignments(page) {
  const assignmentTitle = `QA Assignment ${uniqueId}`

  await goto(page, `/schools/${schoolId}/assignments`)
  await selectFirstNonEmpty(page, '#assignment-class', 'assignment class')
  await selectFirstNonEmpty(page, '#assignment-subject', 'assignment subject')
  await page.locator('#assignment-title').fill(assignmentTitle)
  await page.locator('#assignment-description').fill('Browser QA issued homework.')
  await page.locator('#assignment-due').fill('2026-05-15')
  await page.getByRole('checkbox', { name: 'Published' }).check()
  await page.getByRole('button', { name: /Save assignment/i }).click()
  await expectText(page, 'Assignment saved.')
  const assignmentRow = page.locator('.assignment-row', { hasText: assignmentTitle }).first()
  await assignmentRow.waitFor({ timeout: 15000 })

  await page.locator('#submission-assignment').selectOption({ label: assignmentTitle })
  await selectFirstNonEmpty(page, '#submission-enrollment', 'submission student')
  await page.locator('#submission-time').fill('2026-05-01T10:30')
  await page.locator('#submission-status').selectOption('graded')
  await page.locator('#submission-marks').fill('92')
  await page.locator('#submission-feedback').fill('Strong submission.')
  await page.getByRole('button', { name: /Save submission/i }).click()
  await expectText(page, 'Submission saved.')
  await expectText(page, 'graded')

  await assignmentRow.getByRole('button', { name: /Edit/i }).click()
  await page.locator('#assignment-description').fill('Browser QA updated homework.')
  await page.getByRole('button', { name: /Update assignment/i }).click()
  await expectText(page, 'Assignment updated.')

  record('Assignment create/submission/update', 'pass', assignmentTitle)
}

async function testTimetable(page) {
  await goto(page, `/schools/${schoolId}/timetable`)
  await selectFirstNonEmpty(page, '#period-year', 'period academic year')
  await selectFirstNonEmpty(page, '#period-class', 'period class')
  await page.locator('#period-shift').selectOption('')
  await selectFirstNonEmpty(page, '#period-subject', 'period subject')
  await page.locator('#period-day').selectOption('6')
  await page.locator('#period-number').fill(String(10 + (Number(uniqueId.slice(-1)) % 10)))
  await page.locator('#period-start').fill(`2${Number(uniqueId.slice(-1)) % 3}:00`)
  await page.locator('#period-end').fill(`2${Number(uniqueId.slice(-1)) % 3}:30`)
  await page.locator('#period-room').fill(`QA Room ${uniqueId}`)
  await page.getByRole('button', { name: /Save period/i }).click()
  await expectText(page, 'Timetable period saved.')
  await expectText(page, `QA Room ${uniqueId}`)

  const periodCard = page.locator('.period-card', { hasText: `QA Room ${uniqueId}` }).first()
  await periodCard.getByRole('button', { name: /Edit/i }).click()
  await page.locator('#period-room').fill(`QA Lab ${uniqueId}`)
  await page.getByRole('button', { name: /Update period/i }).click()
  await expectText(page, 'Timetable period updated.')
  await expectText(page, `QA Lab ${uniqueId}`)

  const updatedCard = page.locator('.period-card', { hasText: `QA Lab ${uniqueId}` }).first()
  await updatedCard.getByRole('button', { name: /Archive/i }).click()
  await expectText(page, 'Timetable period archived.')

  record('Timetable create/update/archive', 'pass', `QA Lab ${uniqueId}`)
}

async function main() {
  await prepareState()

  const browser = await chromium.launch({ headless: true })
  const page = await browser.newPage({ viewport: { width: 1440, height: 1080 } })
  const errors = []

  page.on('console', (message) => {
    if (message.type() === 'error' && !isIgnorableConsoleError(message.text()))
      errors.push(message.text())
  })

  try {
    await login(page)
    await testPaymentGateways(page)
    await testDocuments(page)
    await testAssignments(page)
    await testTimetable(page)
    await saveScreenshot(page, `extended-ops-suite-${stamp}.png`)

    if (errors.length)
      throw new Error(`Console errors detected: ${errors.join(' | ')}`)

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
