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

async function resetOfflineStorage(page) {
  await page.evaluate(async () => {
    localStorage.removeItem('school-saas:offline-queue:attendance')
    localStorage.removeItem('school-saas:offline-queue:marks')

    await new Promise((resolve, reject) => {
      const request = indexedDB.deleteDatabase('school-saas-offline')

      request.onsuccess = () => resolve(null)
      request.onerror = () => reject(request.error)
      request.onblocked = () => resolve(null)
    })
  })
}

async function readIndexedQueue(page, namespace) {
  return await page.evaluate(async (queueNamespace) => {
    const db = await new Promise((resolve, reject) => {
      const request = indexedDB.open('school-saas-offline', 1)

      request.onupgradeneeded = () => {
        const db = request.result

        if (!db.objectStoreNames.contains('queues'))
          db.createObjectStore('queues', { keyPath: 'namespace' })
      }
      request.onsuccess = () => resolve(request.result)
      request.onerror = () => reject(request.error)
    })

    try {
      return await new Promise((resolve, reject) => {
        const transaction = db.transaction('queues', 'readonly')
        const request = transaction.objectStore('queues').get(queueNamespace)

        request.onsuccess = () => resolve(request.result?.entries ?? [])
        request.onerror = () => reject(request.error)
      })
    } finally {
      db.close()
    }
  }, namespace)
}

async function seedLegacyQueue(page, namespace, records) {
  await page.evaluate(({ queueNamespace, queueRecords }) => {
    localStorage.setItem(`school-saas:offline-queue:${queueNamespace}`, JSON.stringify(queueRecords))
  }, { queueNamespace: namespace, queueRecords: records })
}

function attendanceRecords(schoolIdValue) {
  const now = new Date().toISOString()

  return [
    {
      id: 'qa-attendance-auth-required',
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
      id: 'qa-attendance-conflict',
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
  ]
}

function marksRecords(schoolIdValue) {
  const now = new Date().toISOString()

  return [
    {
      id: 'qa-marks-auth-required',
      label: 'Marks / QA Auth Required / Annual Exam / Mathematics',
      schoolId: Number(schoolIdValue),
      method: 'POST',
      path: `/schools/${schoolIdValue}/marks-entries`,
      payload: {
        exam_id: 1,
        class_subject_id: 1,
        student_enrollment_id: 1,
        marks_obtained: 78,
        is_absent: false,
        absent_reason: null,
        remarks: 'QA marks auth recovery check',
      },
      status: 'auth_required',
      attempts: 1,
      errorMessage: '401 Unauthenticated.',
      createdAt: now,
      updatedAt: now,
      lastAttemptAt: now,
    },
    {
      id: 'qa-marks-conflict',
      label: 'Marks / QA Conflict / Annual Exam / Mathematics',
      schoolId: Number(schoolIdValue),
      method: 'POST',
      path: `/schools/${schoolIdValue}/marks-entries`,
      payload: {
        exam_id: 1,
        class_subject_id: 1,
        student_enrollment_id: 1,
        marks_obtained: 82,
        is_absent: false,
        absent_reason: null,
        remarks: 'QA marks conflict review check',
      },
      status: 'conflict',
      attempts: 2,
      errorMessage: '422 Marks entry already exists for this exam, subject, and enrollment.',
      createdAt: now,
      updatedAt: now,
      lastAttemptAt: now,
    },
  ]
}

async function verifyWorkspaceQueue(page, namespace, path, records, readyMessage) {
  await page.goto(`${baseURL}${path}`, { waitUntil: 'domcontentloaded' })
  await page.waitForLoadState('load')
  await seedLegacyQueue(page, namespace, records)
  await page.reload({ waitUntil: 'domcontentloaded' })

  await page.getByText('Sign in required', { exact: false }).first().waitFor({ timeout: 20000 })
  await page.getByText('Needs review', { exact: false }).first().waitFor({ timeout: 20000 })
  await page.getByText('Review local payload', { exact: false }).first().click()
  await page.getByText('student_enrollment_id', { exact: false }).first().waitFor({ timeout: 15000 })

  const indexedRecords = await readIndexedQueue(page, namespace)
  const legacyRecord = await page.evaluate(queueNamespace => localStorage.getItem(`school-saas:offline-queue:${queueNamespace}`), namespace)

  if (legacyRecord)
    throw new Error(`${namespace} queue was not migrated out of localStorage.`)

  if (indexedRecords.length !== records.length)
    throw new Error(`${namespace} queue expected ${records.length} IndexedDB records, found ${indexedRecords.length}.`)

  await page.getByRole('button', { name: /^Retry$/i }).first().click()
  await page.getByText(readyMessage, { exact: false }).first().waitFor({ timeout: 15000 })
  await page.getByText('1 ready', { exact: false }).first().waitFor({ timeout: 15000 })
}

async function main() {
  const browser = await chromium.launch({ headless: true })
  const page = await browser.newPage({ viewport: { width: 1440, height: 1080 } })

  try {
    await login(page)
    await resetOfflineStorage(page)

    await verifyWorkspaceQueue(
      page,
      'attendance',
      `/schools/${schoolId}/attendance`,
      attendanceRecords(schoolId),
      'Attendance queue item is ready to retry.',
    )

    await verifyWorkspaceQueue(
      page,
      'marks',
      `/schools/${schoolId}/marks`,
      marksRecords(schoolId),
      'Marks queue item is ready to retry.',
    )

    await saveScreenshot(page, `offline-queue-recovery-${stamp}.png`)

    await page.getByRole('button', { name: /Sign in again/i }).click()
    await page.waitForURL(url => url.pathname === '/login' && url.searchParams.get('redirect') === `/schools/${schoolId}/marks`, { timeout: 20000 })

    console.log('PASS offline queue recovery')
  } finally {
    await browser.close()
  }
}

main().catch((error) => {
  console.error(error)
  process.exitCode = 1
})
