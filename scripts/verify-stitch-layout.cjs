const fs = require('node:fs')
const path = require('node:path')
const { createHash } = require('node:crypto')
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'C:/Users/xtung/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright')
const root = path.resolve(__dirname, '..')
const state = JSON.parse(fs.readFileSync(path.join(process.env.TEMP, 'fitforge-motion-preview.json'), 'utf8').replace(/^\uFEFF/, ''))
if (state.workspace !== root || !/^kiem_tra_hanh_trinh_demo_[a-f0-9]{16}$/.test(state.database)) throw Error('Only isolated design demo is allowed')
const output = path.join(root, 'docs/design/stitch-layout-verification')
fs.mkdirSync(output, { recursive: true })
const report = { environment: 'Chrome / Vue / isolated MySQL demo', createdAt: new Date().toISOString(), cases: [], interactions: [], errors: [], writes: [] }
let browser
const check = (name, passed) => { report.interactions.push({ name, passed }); if (!passed) throw Error(name) }
async function screen(page, name) {
  await page.waitForFunction(() => ![...document.querySelectorAll('[aria-busy=true], .spinner-border')].some(e => e.getBoundingClientRect().width > 0), null, { timeout: 10000 })
  const result = await page.evaluate(() => ({ overflow: document.documentElement.scrollWidth > innerWidth + 1, brokenImages: [...document.images].filter(i => i.complete && !i.naturalWidth && getComputedStyle(i).display !== 'none').map(i => i.src) }))
  await page.screenshot({ path: path.join(output, name + '.jpg'), fullPage: true, type: 'jpeg', quality: 82 })
  report.cases.push({ name, path: new URL(page.url()).pathname, viewport: page.viewportSize(), ...result })
  check(name + ': fits viewport / images', !result.overflow && !result.brokenImages.length)
}
async function open(page, route) {
  await page.goto(state.frontend + route)
  await page.locator('h1').first().waitFor()
}
async function main() {
  const files = JSON.parse(fs.readFileSync(path.join(root, 'docs/design/stitch-approved/files.json'), 'utf8')).files
  let archived = 0
  for (const item of files) for (const kind of ['html', 'png']) {
    const data = fs.readFileSync(path.join(root, 'docs/design/stitch-approved', item[kind].file))
    check(item.id + ': ' + kind + ' archive hash', data.length === item[kind].bytes && createHash('sha256').update(data).digest('hex') === item[kind].sha256)
    archived++
  }
  report.archivedFiles = archived
  browser = await chromium.launch({ channel: 'chrome', headless: true })
  for (const [user, routes] of [
    ['admin', ['/admin/tong-quan', '/admin/phan-cong', '/admin/tai-khoan', '/admin/tai-lieu-tu-van']],
    ['pt', ['/pt/hoc-vien', '/pt/ke-hoach/6/sua', '/pt/tin-nhan/1']],
    ['kh', ['/khach-hang/tong-quan', '/khach-hang/ho-so', '/khach-hang/lich-tap', '/khach-hang/tin-nhan/1']],
  ]) {
    const context = await browser.newContext({ viewport: { width: 768, height: 1024 } })
    const page = await context.newPage()
    page.on('pageerror', e => report.errors.push({ user, error: e.message }))
    await open(page, '/dang-nhap')
    await page.locator('#email').fill(user + '@hanh-trinh.example.test')
    await page.locator('#password').fill('Demo123456!')
    await page.locator('form button[type=submit]').click()
    await page.waitForURL('**/tong-quan')
    page.on('request', r => { if (/\/api\//.test(r.url()) && !['GET', 'HEAD', 'OPTIONS'].includes(r.method())) report.writes.push({ url: r.url(), method: r.method() }) })
    for (const theme of ['light', 'dark']) {
      await page.evaluate(v => localStorage.setItem('gym_theme', v), theme)
      for (const route of routes) {
        await open(page, route)
        await screen(page, user + '-' + route.split('/').at(-1) + '-' + theme + '-768')
      }
    }
    await page.setViewportSize({ width: 390, height: 844 })
    if (user === 'admin') {
      await open(page, '/admin/tai-khoan')
      await page.getByRole('button', { name: 'Tạo tài khoản', exact: true }).click()
      const drawer = page.locator('.st-drawer')
      check('Account drawer is full-height right panel', await drawer.evaluate(e => { const r = e.getBoundingClientRect(); return e.open && r.top === 0 && r.right <= innerWidth && r.bottom <= innerHeight && r.height >= innerHeight - 2 }))
      await page.getByLabel('Đóng form tạo tài khoản').click()
      check('Account drawer restores trigger focus', await page.getByRole('button', { name: 'Tạo tài khoản', exact: true }).evaluate(e => e === document.activeElement))
      await page.getByRole('button', { name: 'Tạo tài khoản', exact: true }).click()
      await screen(page, 'admin-create-drawer-dark-390')
      await page.keyboard.press('Escape')
      check('Escape closes account drawer', !await drawer.evaluate(e => e.open))
      await open(page, '/admin/tai-lieu-tu-van')
      await page.getByRole('button', { name: 'Thêm tài liệu', exact: true }).click()
      await page.locator('#ai-doc-title').fill('Bản nháp bố cục chưa lưu')
      await screen(page, 'admin-document-editor-dark-390')
      await page.getByRole('button', { name: 'Đóng', exact: true }).click()
      check('Document editor closes without publishing', !await page.locator('#ai-doc-title').count())
      await open(page, '/admin/don-hang')
      await page.getByRole('button', { name: /Xem đơn/ }).first().click()
      check('Order preview links to existing detail route', await page.locator('aside[aria-label="Xem nhanh đơn hàng"] a').count() === 1)
      await screen(page, 'admin-order-preview-dark-390')
    }
    if (user === 'pt') {
      await open(page, '/pt/ke-hoach/6/sua')
      await page.locator('.st-exercise-table input').first().fill('4')
      await screen(page, 'pt-editor-parameters-dark-390')
      check('Mobile exercise parameters fit two columns', await page.locator('.st-exercise-table tbody tr').first().evaluate(e => getComputedStyle(e).display === 'grid' && e.getBoundingClientRect().right <= innerWidth))
    }
    if (user === 'kh') {
      await open(page, '/khach-hang/lich-hen')
      await page.getByRole('button', { name: /Xem nhanh buổi/ }).first().click()
      await screen(page, 'kh-appointment-preview-dark-390')
      await open(page, '/khach-hang/lich-tap')
      await page.waitForFunction(() => !document.querySelector('[aria-busy=true]'))
      await page.locator('.st-calendar-heading input').fill('2026-12-29')
      await page.locator('.st-calendar-heading input').dispatchEvent('change')
      await screen(page, 'kh-calendar-week-dark-390')
      check('Week change synchronizes API filter dates', await page.evaluate(() => { const dates = [...document.querySelectorAll('.nk-filter input[type=date]')].map(e => e.value); return dates[0] === '2026-12-29' && dates[1] === '2027-01-04' }))
      await page.getByRole('button', { name: 'Xem tất cả mục điều hướng', exact: true }).click()
      await page.getByRole('button', { name: 'Đóng menu', exact: true }).click()
      check('More menu restores bottom trigger focus', await page.getByRole('button', { name: 'Xem tất cả mục điều hướng', exact: true }).evaluate(e => e === document.activeElement))
      await open(page, '/khach-hang/tin-nhan/1')
      await screen(page, 'kh-chat-dark-390')
      await page.locator('.chat-composer').scrollIntoViewIfNeeded()
      check('Mobile composer stays above bottom navigation', await page.locator('.chat-composer').evaluate(e => e.getBoundingClientRect().bottom <= document.querySelector('.mobile-navigation').getBoundingClientRect().top + 1))
    }
    await context.close()
  }
  check('No JavaScript runtime errors', report.errors.length === 0)
  check('Preview interactions do not submit business operations', report.writes.length === 0)
}
main().catch(e => { report.failure = e.message; process.exitCode = 1; console.error(e) }).finally(async () => {
  fs.writeFileSync(path.join(output, 'verification.json'), JSON.stringify(report, null, 2))
  console.log(JSON.stringify({ cases: report.cases.length, checks: report.interactions.length, failure: report.failure, errors: report.errors.length }))
  await browser?.close()
})
