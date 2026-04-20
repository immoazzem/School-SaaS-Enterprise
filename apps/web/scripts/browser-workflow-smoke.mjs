import { mkdir } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://127.0.0.1:3000'
const schoolId = process.env.QA_SCHOOL_ID || '1'
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
  const found = needles.find((needle) => body.includes(needle))

  if (found) {
    throw new Error(`${name}: visible error text found: ${found}`)
  }
}

async function goto(page, path) {
  await page.goto(`${baseURL}${path}`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(450)
  await assertNoVisibleErrors(page, path)
}

async function login(page) {
  await page.goto(baseURL, { waitUntil: 'networkidle' })
  await page.getByLabel('Email').fill('test@example.com')
  await page.getByLabel('Password').fill('password')
  await page.getByRole('button', { name: 'Continue' }).click()
  await page.waitForURL('**/dashboard', { timeout: 20000 })
  await assertNoVisibleErrors(page, 'login')
}

async function fillLabel(page, label, value, index = 0) {
  await page.getByLabel(label, { exact: true }).nth(index).fill(String(value))
}

async function selectLabel(page, label, valueOrLabel, index = 0) {
  const field = page.getByLabel(label, { exact: true }).nth(index)
  try {
    await field.selectOption({ label: String(valueOrLabel) })
  } catch {
    try {
      await field.selectOption(String(valueOrLabel))
    } catch {
      const matchedValue = await field.evaluate((select, expected) => {
        const normalized = String(expected).toLowerCase()
        const options = Array.from(select.options)
        const match = options.find((option) => option.textContent?.trim().toLowerCase().includes(normalized))

        return match?.value ?? null
      }, String(valueOrLabel))

      if (!matchedValue) {
        throw new Error(`Option not found for ${label}: ${valueOrLabel}`)
      }

      await field.selectOption(matchedValue)
    }
  }
}

async function selectFirstNonEmptyLabel(page, label, index = 0) {
  const field = page.getByLabel(label, { exact: true }).nth(index)
  const matchedValue = await field.evaluate((select) => {
    const option = Array.from(select.options).find((item) => item.value)

    return option?.value ?? null
  })

  if (!matchedValue) {
    throw new Error(`No selectable option found for ${label}`)
  }

  await field.selectOption(matchedValue)
}

async function clickButton(page, name, index = 0) {
  await page.getByRole('button', { name, exact: true }).nth(index).click()
}

async function expectText(page, text) {
  await page.getByText(text, { exact: false }).first().waitFor({ timeout: 12000 })
}

async function archiveRow(page, rowText, button = 'Archive') {
  const row = page.getByRole('row').filter({ hasText: rowText }).first()
  await row.getByRole('button', { name: button }).click()
}

function artifactPath(fileName) {
  return resolve(artifactDir, fileName)
}

async function testAcademicClass(page) {
  const name = `QA Browser Class ${stamp}`
  const code = `QA-CLS-${stamp}`
  await goto(page, `/schools/${schoolId}/academic-classes`)
  await fillLabel(page, 'Name', name)
  await fillLabel(page, 'Code', code)
  await fillLabel(page, 'Sort order', '42')
  await clickButton(page, 'Save class')
  await expectText(page, name)
  await archiveRow(page, name)
  await page.waitForTimeout(600)
  record('Academic classes create/archive', 'pass', code)
}

async function testAcademicSection(page) {
  const name = `QA Section ${stamp}`
  const code = `QA-SEC-${stamp}`
  await goto(page, `/schools/${schoolId}/academic-sections`)
  await selectLabel(page, 'Class', 'Class One')
  await fillLabel(page, 'Name', name)
  await fillLabel(page, 'Code', code)
  await fillLabel(page, 'Capacity', '28')
  await fillLabel(page, 'Room', `Q${stamp.slice(-4)}`)
  await fillLabel(page, 'Order', '44')
  await clickButton(page, 'Save section')
  await expectText(page, name)
  await page.getByRole('row').filter({ hasText: name }).getByRole('button', { name: 'Edit' }).click()
  await fillLabel(page, 'Room', `QE${stamp.slice(-4)}`)
  await clickButton(page, 'Update section')
  await expectText(page, `QE${stamp.slice(-4)}`)
  await archiveRow(page, name)
  record('Academic sections create/update/archive', 'pass', code)
}

async function testAcademicYear(page) {
  const name = `QA Academic Year ${stamp}`
  const code = `QA-AY-${stamp}`
  await goto(page, `/schools/${schoolId}/academic-years`)
  await fillLabel(page, 'Name', name)
  await fillLabel(page, 'Code', code)
  await fillLabel(page, 'Starts on', '2027-01-01')
  await fillLabel(page, 'Ends on', '2027-12-31')
  await clickButton(page, 'Save year')
  await expectText(page, name)
  await page.getByRole('row').filter({ hasText: name }).getByRole('button', { name: 'Edit' }).click()
  await fillLabel(page, 'Name', `${name} Updated`)
  await clickButton(page, 'Update year')
  await expectText(page, `${name} Updated`)
  await archiveRow(page, `${name} Updated`)
  record('Academic years create/update/archive', 'pass', code)
}

async function testSubject(page) {
  const name = `QA Subject ${stamp}`
  const code = `QA-SUB-${stamp}`
  await goto(page, `/schools/${schoolId}/subjects`)
  await fillLabel(page, 'Name', name)
  await fillLabel(page, 'Code', code)
  await selectLabel(page, 'Type', 'Core')
  await fillLabel(page, 'Credit hours', '3')
  await fillLabel(page, 'Order', '43')
  await clickButton(page, 'Save subject')
  await expectText(page, name)
  await page.getByRole('row').filter({ hasText: name }).getByRole('button', { name: 'Edit' }).click()
  await fillLabel(page, 'Credit hours', '4')
  await clickButton(page, 'Update subject')
  await archiveRow(page, name)
  record('Subjects create/update/archive', 'pass', code)
}

async function testGroupShiftDesignation(page) {
  const groupName = `QA Group ${stamp}`
  await goto(page, `/schools/${schoolId}/student-groups`)
  await fillLabel(page, 'Name', groupName)
  await fillLabel(page, 'Code', `QA-GRP-${stamp}`)
  await fillLabel(page, 'Order', '40')
  await clickButton(page, 'Save group')
  await expectText(page, groupName)
  await archiveRow(page, groupName)

  const shiftName = `QA Shift ${stamp}`
  await goto(page, `/schools/${schoolId}/shifts`)
  await fillLabel(page, 'Name', shiftName)
  await fillLabel(page, 'Code', `QA-SH-${stamp}`)
  await fillLabel(page, 'Starts', '17:00')
  await fillLabel(page, 'Ends', '19:00')
  await fillLabel(page, 'Order', '40')
  await clickButton(page, 'Save shift')
  await expectText(page, shiftName)
  await archiveRow(page, shiftName)

  const designationName = `QA Designation ${stamp}`
  await goto(page, `/schools/${schoolId}/designations`)
  await fillLabel(page, 'Name', designationName)
  await fillLabel(page, 'Code', `QA-DES-${stamp}`)
  await fillLabel(page, 'Order', '40')
  await clickButton(page, 'Save designation')
  await expectText(page, designationName)
  await archiveRow(page, designationName)

  record('Groups, shifts, designations create/archive', 'pass', stamp)
}

async function testStudentGuardian(page) {
  const guardian = `QA Guardian ${stamp}`
  const student = `QA Student ${stamp}`
  await goto(page, `/schools/${schoolId}/students`)
  await fillLabel(page, 'Name', guardian)
  await fillLabel(page, 'Relationship', 'Father')
  await fillLabel(page, 'Phone', '+8801712345678', 0)
  await clickButton(page, 'Save guardian')
  await expectText(page, 'Guardian saved.')

  await fillLabel(page, 'Admission no', `QA-ADM-${stamp}`)
  await fillLabel(page, 'Name', student, 1)
  await selectLabel(page, 'Guardian', guardian)
  await fillLabel(page, 'Gender', 'Male')
  await fillLabel(page, 'Date of birth', '2017-04-21')
  await fillLabel(page, 'Admitted on', '2026-04-21')
  await clickButton(page, 'Save student')
  await page.locator('input[placeholder="Search"]').nth(1).fill(student)
  await page.getByRole('button', { name: 'Search' }).nth(1).click()
  await expectText(page, student)

  await page.getByRole('row').filter({ hasText: student }).getByRole('button', { name: 'Edit' }).click()
  await fillLabel(page, 'Phone', '+8801799999999', 1)
  await clickButton(page, 'Update student')
  await expectText(page, 'Student updated.')
  await archiveRow(page, student)
  await page.locator('input[placeholder="Search"]').first().fill(guardian)
  await page.getByRole('button', { name: 'Search' }).first().click()
  await archiveRow(page, guardian)
  record('Guardians/students create/update/archive', 'pass', stamp)
}

async function testAttendance(page) {
  const day = String((Number(stamp.slice(-2)) % 27) + 1).padStart(2, '0')
  const attendanceDate = `2026-08-${day}`
  await goto(page, `/schools/${schoolId}/attendance`)
  await selectFirstNonEmptyLabel(page, 'Student enrollment')
  await fillLabel(page, 'Date', attendanceDate)
  await selectLabel(page, 'Status', 'Late')
  await fillLabel(page, 'Remarks', `QA attendance ${stamp}`)
  await clickButton(page, 'Save attendance')
  await expectText(page, 'Attendance saved.')
  await page.getByRole('row').filter({ hasText: attendanceDate }).getByRole('button', { name: 'Edit' }).click()
  await selectLabel(page, 'Status', 'Present')
  await fillLabel(page, 'Remarks', `QA attendance updated ${stamp}`)
  await clickButton(page, 'Update attendance')
  await expectText(page, 'Attendance updated.')
  await page.getByRole('row').filter({ hasText: attendanceDate }).getByRole('button', { name: 'Delete' }).click()
  record('Attendance create/update/delete', 'pass', stamp)
}

async function testCalendar(page) {
  const title = `QA Calendar Event ${stamp}`
  await goto(page, `/schools/${schoolId}/calendar`)
  await fillLabel(page, 'Title', title)
  await fillLabel(page, 'Starts on', '2026-09-01')
  await fillLabel(page, 'Ends on', '2026-09-01')
  await fillLabel(page, 'Starts at', '10:00')
  await fillLabel(page, 'Ends at', '11:00')
  await fillLabel(page, 'Location', 'QA Hall')
  await clickButton(page, 'Save event')
  await expectText(page, title)
  record('Calendar event create', 'pass', stamp)
}

async function testFinance(page) {
  const categoryName = `QA Fee ${stamp}`
  const billingMonth = `2026-${String((Number(stamp.slice(-2)) % 12) + 1).padStart(2, '0')}`
  await goto(page, `/schools/${schoolId}/finance`)
  await fillLabel(page, 'Name', categoryName)
  await fillLabel(page, 'Code', `QA-FEE-${stamp}`)
  await selectLabel(page, 'Billing type', 'Optional')
  await clickButton(page, 'Save category')
  await expectText(page, 'Fee category saved.')

  await selectLabel(page, 'Fee category', categoryName)
  await selectFirstNonEmptyLabel(page, 'Academic year')
  await selectFirstNonEmptyLabel(page, 'Class')
  await fillLabel(page, 'Amount', '1250')
  await fillLabel(page, 'Due day', '12')
  await clickButton(page, 'Save structure')
  await expectText(page, 'Fee structure saved.')

  await selectFirstNonEmptyLabel(page, 'Student')
  await selectFirstNonEmptyLabel(page, 'Academic year', 1)
  await fillLabel(page, 'Fee month', billingMonth)
  await selectLabel(page, 'Fee structure', categoryName)
  await clickButton(page, 'Create invoice')
  await expectText(page, 'Invoice created.')

  await selectFirstNonEmptyLabel(page, 'Class', 1)
  await fillLabel(page, 'Month', billingMonth)
  await selectFirstNonEmptyLabel(page, 'Academic year', 2)
  await selectLabel(page, 'Fee structure', categoryName, 1)
  await clickButton(page, 'Queue invoices')
  await expectText(page, 'Bulk invoices queued:')
  record('Finance category/structure/invoice/bulk queue', 'pass', stamp)
}

async function testReports(page) {
  await goto(page, `/schools/${schoolId}/reports`)
  await clickButton(page, 'Queue marksheet')
  await expectText(page, 'Report queued:')
  await clickButton(page, 'Check file')
  record('Reports marksheet export queue/check', 'pass', stamp)
}

async function runStep(name, fn, page) {
  try {
    await fn(page)
  } catch (error) {
    record(name, 'fail', error.message)
    await page.screenshot({
      path: artifactPath(`workflow-failure-${name.replace(/[^a-z0-9]+/gi, '-').toLowerCase()}-${stamp}.png`),
      fullPage: true,
    })
    throw error
  }
}

await mkdir(artifactDir, { recursive: true })

const browser = await chromium.launch({ headless: true })
const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 1100 } })
const page = await context.newPage()

const browserErrors = []
page.on('pageerror', (error) => browserErrors.push(error.message))
page.on('console', (message) => {
  if (['error'].includes(message.type())) {
    browserErrors.push(message.text())
  }
})

try {
  await login(page)
  await runStep('Academic classes', testAcademicClass, page)
  await runStep('Academic sections', testAcademicSection, page)
  await runStep('Academic years', testAcademicYear, page)
  await runStep('Subjects', testSubject, page)
  await runStep('Groups shifts designations', testGroupShiftDesignation, page)
  await runStep('Students guardians', testStudentGuardian, page)
  await runStep('Attendance', testAttendance, page)
  await runStep('Calendar', testCalendar, page)
  await runStep('Finance', testFinance, page)
  await runStep('Reports', testReports, page)

  if (browserErrors.length) {
    throw new Error(`Browser console errors: ${browserErrors.join(' | ')}`)
  }

  await page.screenshot({ path: artifactPath(`workflow-smoke-${stamp}.png`), fullPage: true })
  console.log(`Workflow smoke completed with ${results.filter((result) => result.status === 'pass').length} passed checks.`)
} finally {
  await browser.close()
}
