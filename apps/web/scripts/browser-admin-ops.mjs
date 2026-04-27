import { mkdir } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium } from 'playwright'

const baseURL = process.env.QA_BASE_URL || 'http://localhost:3000'
const schoolId = process.env.QA_SCHOOL_ID || '1'
const stamp = process.env.QA_STAMP || new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14)
const qaEmail = process.env.QA_EMAIL || 'superadmin@example.com'
const qaPassword = process.env.QA_PASSWORD || 'password'
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
  await page.waitForTimeout(600)
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

async function testAdminRoutes(page) {
  const routes = [
    { path: '/admin', text: 'Enterprise workspace' },
    { path: '/admin/schools', text: 'Admin schools' },
    { path: '/admin/users', text: 'Admin users' },
    { path: '/admin/jobs', text: 'Admin jobs' },
    { path: '/admin/audit-logs', text: 'Admin audit logs' },
  ]

  for (const route of routes) {
    await goto(page, route.path)
    await expectText(page, route.text)
  }

  record('Enterprise admin routes load/search', 'pass')
}

async function testInvitations(page) {
  const inviteEmail = `qa-admin-${stamp}@example.com`

  await goto(page, `/schools/${schoolId}/invitations`)
  await expectText(page, 'Invitation register')
  await page.getByLabel('Email').fill(inviteEmail)
  await page.getByLabel('Name').fill('Admin Ops Invite')
  await page.getByRole('button', { name: /Send invitation/i }).click()
  await expectText(page, 'Invitation created.')
  const invitationRow = page.locator('tbody tr', { hasText: inviteEmail }).first()
  await invitationRow.waitFor({ timeout: 15000 })
  await invitationRow.getByRole('button', { name: 'Revoke' }).click()
  await expectText(page, 'Invitation revoked.')
  await invitationRow.getByText('revoked', { exact: false }).waitFor({ timeout: 15000 })

  record('Invitation create/revoke', 'pass', inviteEmail)
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
    await testAdminRoutes(page)
    await testInvitations(page)
    await saveScreenshot(page, `admin-ops-suite-${stamp}.png`)

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
