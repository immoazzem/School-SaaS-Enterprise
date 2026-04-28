import { mkdir } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://localhost:3000'
const schoolId = process.env.QA_SCHOOL_ID || '1'
const qaEmail = process.env.QA_EMAIL || 'superadmin@example.com'
const qaPassword = process.env.QA_PASSWORD || 'password'
const stamp = process.env.QA_STAMP || new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14)
const uniqueId = stamp.slice(-6)
const salaryMonth = `2027-${String((Number(uniqueId.slice(-2)) % 12) + 1).padStart(2, '0')}`
const invoiceMonth = '2026-04'
const scriptDir = dirname(fileURLToPath(import.meta.url))
const repoRoot = resolve(scriptDir, '../../..')
const artifactDir = resolve(repoRoot, 'docs/browser-checks')

const results = []

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
  await emailField.waitFor({ timeout: 60000 })
  await emailField.fill(qaEmail)
  await passwordField.fill(qaPassword)
  await page.getByRole('button', { name: /Enter workspace/i })
    .or(page.getByRole('button', { name: /Continue to Workspace/i }))
    .or(page.getByRole('button', { name: /^Continue$/i }))
    .click()
  await page.waitForFunction(() => !window.location.pathname.startsWith('/login'), { timeout: 60000 })
}

async function expectText(page, text, timeout = 15000) {
  await page.getByText(text, { exact: false }).first().waitFor({ timeout })
}

async function createEditArchiveEmployee(page) {
  const employeeName = `QA Employee ${uniqueId}`

  await goto(page, `/schools/${schoolId}/employees`)
  await page.locator('#employee-no').fill(`EMP-${uniqueId}`)
  await page.locator('#employee-name').fill(employeeName)
  await page.locator('#employee-email').fill(`qa-emp-${uniqueId}@example.com`)
  await page.locator('#employee-phone').fill(`017${uniqueId}`)
  await page.locator('#employee-joined').fill('2026-04-23')
  await page.locator('#employee-salary').fill('28000')
  await page.getByRole('button', { name: /Save employee/i }).click()
  await expectText(page, 'saved')

  const employeeRow = page.locator('tbody tr', { hasText: employeeName }).first()
  await employeeRow.waitFor({ timeout: 15000 })
  await employeeRow.getByRole('button', { name: /Edit/i }).click()
  await page.locator('#employee-phone').fill(`018${uniqueId}`)
  await page.getByRole('button', { name: /Update employee/i }).click()
  await expectText(page, 'updated')

  record('Employee create/edit', 'pass', employeeName)
  return employeeName
}

async function runStaffOps(page, employeeName) {
  await goto(page, `/schools/${schoolId}/staff-operations`)
  await page.locator('#salary-employee').selectOption({ label: employeeName })
  await page.locator('#salary-academic-year').selectOption({ index: 1 })
  await page.locator('#salary-month').fill(salaryMonth)
  await page.locator('#salary-basic-amount').fill('30000')
  await page.getByRole('button', { name: /Save salary/i }).click()
  await expectText(page, 'Salary record saved.')

  await page.locator('#staff-attendance-employee').selectOption({ label: employeeName })
  await page.locator('#staff-attendance-date').fill('2026-04-23')
  await page.getByRole('button', { name: /Save attendance/i }).click()
  await expectText(page, 'Employee attendance saved.')

  record('Staff ops salary/attendance', 'pass', employeeName)
}

async function archiveEmployee(page, employeeName) {
  await goto(page, `/schools/${schoolId}/employees`)
  const employeeRow = page.locator('tbody tr', { hasText: employeeName }).first()
  await employeeRow.waitFor({ timeout: 15000 })
  await employeeRow.getByRole('button', { name: /Archive/i }).click()
  await expectText(page, 'Employee archived.')

  record('Employee archive', 'pass', employeeName)
}

async function runFinance(page) {
  const feeName = `QA Fee ${uniqueId}`
  const discountName = `QA Discount ${uniqueId}`

  await goto(page, `/schools/${schoolId}/finance`)
  await page.locator('#fee-category-name').fill(feeName)
  await page.locator('#fee-category-code').fill(`QF${uniqueId}`)
  await page.getByRole('button', { name: /Save category/i }).click()
  await expectText(page, 'Fee category saved.')

  await page.locator('#fee-structure-category').selectOption({ label: feeName })
  await page.locator('#fee-structure-year').selectOption({ index: 1 })
  await page.locator('#fee-structure-class').selectOption({ index: 1 })
  await page.locator('#fee-structure-amount').fill('1500')
  await page.getByRole('button', { name: /Save structure/i }).click()
  await expectText(page, 'Fee structure saved.')

  await page.locator('#manual-invoice-student').selectOption({ index: 1 })
  await page.locator('#manual-invoice-year').selectOption({ index: 1 })
  await page.locator('#manual-invoice-month').fill(invoiceMonth)
  await page.locator('#manual-invoice-structure').selectOption({ index: 1 })
  await page.getByRole('button', { name: /Create invoice/i }).click()
  await expectText(page, 'Invoice created.')

  record('Finance category/structure/invoice', 'pass', feeName)

  await goto(page, `/schools/${schoolId}/invoice-payments`)
  await page.locator('#invoice-payment-invoice').selectOption({ index: 1 })
  await page.locator('#invoice-payment-amount').fill('100')
  await page.locator('#invoice-payment-paid-on').fill('2026-04-23')
  await page.getByRole('button', { name: /Record payment/i }).click()
  await expectText(page, 'Invoice payment recorded.')

  record('Invoice payment', 'pass')

  await goto(page, `/schools/${schoolId}/discounts`)
  await page.locator('#discount-policy-name').fill(discountName)
  await page.locator('#discount-policy-code').fill(`QD${uniqueId}`)
  await page.locator('#discount-policy-amount').fill('50')
  await page.getByRole('button', { name: /Save policy/i }).click()
  await expectText(page, 'Discount policy saved.')

  record('Discount policy', 'pass', discountName)
}

async function runAdminOnboarding(page) {
  await goto(page, '/admin/schools')
  await page.locator('tbody tr').first().getByRole('button', { name: /Onboard/i }).click()
  await expectText(page, 'School onboarding completed.')

  record('Admin school onboarding', 'pass')
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
    const employeeName = await createEditArchiveEmployee(page)
    await runStaffOps(page, employeeName)
    await runFinance(page)
    await runAdminOnboarding(page)
    await archiveEmployee(page, employeeName)
    await saveScreenshot(page, `ops-mutation-suite-${stamp}.png`)

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
