const fs = require('node:fs')
const path = require('node:path')
const { randomUUID } = require('node:crypto')
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'C:/Users/xtung/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright')
const workspace = path.resolve(__dirname, '..')
const state = JSON.parse(fs.readFileSync(path.join(process.env.TEMP, 'fitforge-motion-preview.json'), 'utf8').replace(/^\uFEFF/, ''))
if (state.workspace !== workspace || !/^kiem_tra_hanh_trinh_demo_[a-f0-9]{16}$/.test(state.database)) throw Error('Only isolated design demo is allowed')
const output = path.join(workspace, 'docs/design/fitness-motion-app')
fs.mkdirSync(output, { recursive: true })
const report = { environment: 'Chrome / Vue dev server / isolated MySQL demo', cases: [], interactions: [], errors: [] }
const previous = process.argv.includes('--refresh') ? JSON.parse(fs.readFileSync(path.join(output, 'verification.json'), 'utf8')) : null
const source = fs.readFileSync(path.join(workspace, 'FE/src/router/index.js'), 'utf8')
const definition = source.slice(source.indexOf('const router ='), source.indexOf('// Router')).replace('const router =', 'return').replace('import.meta.env.BASE_URL', "'/'")
const routes = new Function('createRouter', 'createWebHistory', definition)(v => v, () => '').routes.filter(r => !r.redirect)
const ids = { bai: 1, nhom: 1, goi: 1, mau: 1, ptDraft: 2, khDraft: 3, hen: 1, chat: 1, don: 1 }
if (previous) {
  const idFor = route => Number(previous.cases.find(c => c.route === route)?.path.match(/\/(\d+)(?:\/sua)?$/)?.[1])
  Object.assign(ids, previous.ids || { bai: idFor('/bai-tap/:id'), nhom: idFor('/admin/nhom-co/:id/sua'), goi: idFor('/goi-tap/:id'), mau: idFor('/pt/giao-an-mau/:id'), ptDraft: idFor('/pt/ke-hoach/:id/sua'), khDraft: idFor('/khach-hang/ke-hoach/:id/sua'), hen: idFor('/khach-hang/lich-hen/:id'), chat: idFor('/khach-hang/tin-nhan/:id'), don: idFor('/khach-hang/don-hang/:id') })
}
report.ids = ids
if (process.argv.includes('--only-chat')) report.cases = previous.cases.filter(c => !c.path.includes('/tin-nhan'))
let browser
async function api(page, route, data, method = 'get') {
  return page.evaluate(async ({ route, data, method }) => {
    const http = (await import('/src/utils/http.js')).default
    if (method !== 'get') await (await import('/src/services/xacThucService.js')).default.layCsrfCookie()
    try { return (await http[method](route, data)).data }
    catch (error) { throw new Error(route + ': ' + JSON.stringify(error.response?.data || error.message)) }
  }, { route, data, method })
}
const rows = response => Array.isArray(response.data) ? response.data : response.data?.data || []
async function login(context, user) {
  const page = await context.newPage()
  await page.goto(state.frontend + '/dang-nhap')
  await page.locator('#email').fill(user + '@hanh-trinh.example.test')
  await page.locator('#password').fill('Demo123456!')
  await page.locator('form button[type=submit]').click()
  await page.waitForURL('**/tong-quan')
  await page.locator('h1').first().waitFor()
  return page
}
async function audit(page) {
  return page.evaluate(() => {
    const width = innerWidth
    const overflow = [...document.querySelectorAll('#app *')].filter(e => {
      const r = e.getBoundingClientRect(), s = getComputedStyle(e)
      if (!r.width || !r.height || s.position === 'fixed' || e.closest('.phone-perspective-stage, .table-responsive, [class*=table-scroll], .ga-days, .kh-days, .nk-calendar, .chat-image-grid')) return false
      let p = e.parentElement
      while (p && p.id !== 'app') { if (['auto', 'scroll', 'hidden', 'clip'].includes(getComputedStyle(p).overflowX)) return false; p = p.parentElement }
      return r.left < -1 || r.right > width + 1
    }).slice(0, 15).map(e => ({ tag: e.tagName, class: e.className, text: e.textContent.trim().slice(0, 75) }))
    const canvas = document.createElement('canvas'); canvas.width = canvas.height = 1
    const c = canvas.getContext('2d', { willReadFrequently: true })
    const rgb = value => { c.clearRect(0, 0, 1, 1); c.fillStyle = value; c.fillRect(0, 0, 1, 1); return [...c.getImageData(0, 0, 1, 1).data] }
    const luminance = a => a.slice(0, 3).map(v => v / 255).map(v => v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4).reduce((t, v, i) => t + v * [0.2126, 0.7152, 0.0722][i], 0)
    const contrast = []
    for (const e of document.querySelectorAll('#app *')) {
      const s = getComputedStyle(e), r = e.getBoundingClientRect()
      if (!r.width || !r.height || e.children.length || !e.textContent.trim() || e.closest('.phone-perspective-stage, svg, [aria-hidden=true], [disabled]') || s.opacity < 1) continue
      const foreground = rgb(s.color)
      if (foreground[3] < 250) continue
      const ancestors = []; let p = e
      while (p) { ancestors.unshift(p); p = p.parentElement }
      let background = [255, 255, 255]
      for (const a of ancestors) { const v = rgb(getComputedStyle(a).backgroundColor), alpha = v[3] / 255; background = background.map((b, i) => v[i] * alpha + b * (1 - alpha)) }
      const l1 = luminance(foreground), l2 = luminance(background), ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05)
      if (ratio < 3) contrast.push({ class: e.className, text: e.textContent.trim().slice(0, 60), ratio: +ratio.toFixed(2), color: s.color, background })
    }
    return { horizontalOverflow: document.documentElement.scrollWidth > width + 1, overflow, contrast: contrast.slice(0, 20), brokenImages: [...document.images].filter(i => i.complete && !i.naturalWidth && getComputedStyle(i).display !== 'none').map(i => i.src), heading: document.querySelector('h1')?.textContent.trim() }
  })
}
function materialize(route) {
  let result = route.path.replace(':khachId', state.fixture.khach_id)
  let id = 1
  if (result.includes('bai-tap')) id = ids.bai
  else if (result.includes('nhom-co')) id = ids.nhom
  else if (result.includes('giao-an-mau')) id = ids.mau
  else if (result.includes('goi-tap')) id = ids.goi
  else if (result.includes('lich-tap')) id = state.fixture.lich_tap_id
  else if (result.includes('ke-hoach')) id = result.endsWith('/sua') ? (route.meta?.vaiTro === 'KHACH_HANG' ? ids.khDraft : ids.ptDraft) : state.fixture.giao_an_id
  else if (result.includes('lich-hen')) id = ids.hen
  else if (result.includes('tin-nhan')) id = ids.chat
  else if (result.includes('don-hang')) id = ids.don
  return result.replace(':id', id)
}
async function main() {
  browser = await chromium.launch({ channel: 'chrome', headless: true })
  const contexts = {}
  for (const [role, user] of [['ADMIN', 'admin'], ['HUAN_LUYEN_VIEN', 'pt'], ['KHACH_HANG', 'kh']]) {
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } })
    const page = await login(context, user)
    contexts[role] = { context, page }
  }
  const admin = contexts.ADMIN.page, pt = contexts.HUAN_LUYEN_VIEN.page, kh = contexts.KHACH_HANG.page
  if (!previous) {
  ids.bai = rows(await api(admin, '/admin/bai-tap'))[0].id
  ids.nhom = rows(await api(admin, '/admin/nhom-co'))[0].id
  ids.goi = rows(await api(admin, '/admin/goi-tap'))[0].id
  const currentTemplates = rows(await api(admin, '/admin/giao-an-mau'))
  const templateBody = { ten_giao_an: 'Giáo án mẫu kiểm tra giao diện', muc_tieu: 'Dữ liệu giả để xem bố cục', so_ngay_tap: 1, client_request_id: randomUUID(), bai_tap: [{ bai_tap_id: ids.bai, ngay_thu: 1, thu_tu: 1, so_hiep: 3, so_lan_lap: 12, nghi_giay: 60, ghi_chu: 'Giữ tư thế kiểm soát.' }] }
  const template = currentTemplates[0] || (await api(admin, '/admin/giao-an-mau', templateBody, 'post')).data
  ids.mau = template.id
  if (template.trang_thai !== 'DA_DUYET') await api(admin, '/admin/giao-an-mau/' + ids.mau + '/trang-thai', { trang_thai: 'DA_DUYET', updated_at: template.updated_at }, 'patch')
  const draftBody = { ten_ke_hoach: 'Giáo án bản nháp giao diện', muc_tieu: 'Dữ liệu kiểm tra', so_ngay_tap: 1, giao_an_mau_id: null, client_request_id: randomUUID(), bai_tap: templateBody.bai_tap.map(b => ({ ...b, muc_ta_kg: null })) }
  ids.ptDraft = (await api(pt, '/pt/hoc-vien/' + state.fixture.khach_id + '/ke-hoach', draftBody, 'post')).data.id
  ids.khDraft = (await api(kh, '/khach-hang/ke-hoach', { ...draftBody, client_request_id: randomUUID() }, 'post')).data.id
  const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10)
  let slot = rows(await api(pt, '/pt/khung-gio?ngay=' + tomorrow))[0]
  if (!slot) {
    slot = (await api(pt, '/pt/khung-gio', { bat_dau_luc: tomorrow + 'T09:00:00+07:00' }, 'post')).data
  }
  const appointments = rows(await api(kh, '/khach-hang/lich-hen'))
  ids.hen = (appointments[0] || (await api(kh, '/khach-hang/lich-hen', { khung_gio_id: slot.id, client_request_id: randomUUID() }, 'post')).data).id
  ids.chat = rows(await api(kh, '/hoi-thoai'))[0].id
  ids.don = rows(await api(kh, '/khach-hang/don-hang'))[0].id
  await api(kh, `/hoi-thoai/${ids.chat}/tin-nhan`, { noi_dung: 'Em đã cập nhật nhật ký buổi tập hôm nay.', client_message_id: randomUUID() }, 'post')
  await api(pt, `/hoi-thoai/${ids.chat}/tin-nhan`, { noi_dung: 'Thầy đã xem. Em nhớ giữ tư thế và ghi mức tạ thực tế nhé.', client_message_id: randomUUID() }, 'post')
  }
  const guestContext = await browser.newContext({ viewport: { width: 1440, height: 900 } })
  contexts.PUBLIC = { context: guestContext, page: await guestContext.newPage() }
  for (const [role, item] of Object.entries(contexts)) {
    const page = item.page
    page.on('pageerror', e => report.errors.push({ role, error: e.message, url: page.url() }))
    const selected = routes.filter(r => (r.meta?.vaiTro || 'PUBLIC') === role || (role !== 'PUBLIC' && /^\/(bai-tap|goi-tap|faq)(\/|$)/.test(r.path))).filter(r => !process.argv.includes('--only-chat') || r.path.includes('/tin-nhan'))
    for (const theme of ['light', 'dark']) {
      await page.goto(state.frontend)
      await page.evaluate(theme => localStorage.setItem('gym_theme', theme), theme)
      for (const viewport of [{ width: 1440, height: 900 }, { width: 390, height: 844 }]) {
        await page.setViewportSize(viewport)
        for (const route of selected) {
          const url = materialize(route)
          const name = role.toLowerCase() + '-' + url.replace(/\//g, '-').replace(/^-/, '') + '-' + theme + '-' + viewport.width
          try {
            await page.goto(state.frontend + url)
            await page.locator('h1').first().waitFor({ timeout: 15000 })
            await page.waitForTimeout(650)
            await page.waitForFunction(() => ![...document.querySelectorAll('[aria-busy=true], .spinner-border')].some(e => e.getBoundingClientRect().width > 0), null, { timeout: 10000 })
            const result = await audit(page)
            const screenshot = name + '.jpg'
            await page.screenshot({ path: path.join(output, screenshot), fullPage: true, type: 'jpeg', quality: 78 })
            report.cases.push({ role, path: url, route: route.path, theme, viewport, screenshot, actualPath: new URL(page.url()).pathname, ...result })
            console.log(JSON.stringify({ role, path: url, theme, width: viewport.width, overflow: result.horizontalOverflow, contrast: result.contrast.length }))
          } catch (e) { report.cases.push({ role, path: url, theme, viewport, error: e.message }); console.log('FAIL', role, url, e.message.slice(0, 100)) }
          fs.writeFileSync(path.join(output, 'verification.json'), JSON.stringify(report, null, 2))
        }
      }
    }
  }
  const home = contexts.PUBLIC.page
  await home.setViewportSize({ width: 1440, height: 900 })
  await home.goto(state.frontend + '/')
  await home.locator('.phone-screen-img').waitFor()
  await home.waitForTimeout(500)
  const phone = home.locator('.phone-tilt-rig')
  const before = await phone.evaluate(e => getComputedStyle(e).transform)
  await home.locator('.phone-perspective-stage').hover({ position: { x: 50, y: 90 } })
  await home.waitForTimeout(400)
  const after = await phone.evaluate(e => getComputedStyle(e).transform)
  report.interactions.push({ name: 'Homepage phone image / 3 badges / hover', passed: before !== after && await home.locator('.floating-ui-badge').count() === 3 && await home.locator('.phone-screen-img').evaluate(i => i.naturalWidth > 0) })
  report.interactions.push({ name: 'Phone bitmap nonblank pixel check', passed: await home.locator('.phone-screen-img').evaluate(i => { const c = document.createElement('canvas'); c.width = c.height = 80; const x = c.getContext('2d'); x.drawImage(i, 0, 0, 80, 80); const p = x.getImageData(0, 0, 80, 80).data; let min = 255, max = 0; for (let k = 0; k < p.length; k += 4) { min = Math.min(min, p[k]); max = Math.max(max, p[k]); } return max - min > 100 }) })
  const member = contexts.KHACH_HANG.page
  await member.setViewportSize({ width: 390, height: 844 }); await member.goto(state.frontend + '/khach-hang/tong-quan')
  await member.getByRole('button', { name: 'Mở menu điều hướng', exact: true }).click()
  const opened = await member.locator('.sidebar-drawer').evaluate(d => d.open)
  await member.getByRole('button', { name: 'Đóng menu', exact: true }).click()
  report.interactions.push({ name: 'Mobile navigation drawer open / close', passed: opened && !await member.locator('.sidebar-drawer').evaluate(d => d.open) })
  const themeButton = member.locator('.btn-theme-toggle'); const oldTheme = await member.locator('html').getAttribute('data-theme')
  await themeButton.click(); await member.reload(); await member.locator('h1').waitFor()
  report.interactions.push({ name: 'Theme switch persists after reload', passed: oldTheme !== await member.locator('html').getAttribute('data-theme') })
  fs.writeFileSync(path.join(output, 'verification.json'), JSON.stringify(report, null, 2))
  console.log('COMPLETE', report.cases.length, 'cases;', report.errors.length, 'JS errors')
}
main().catch(e => { console.error(e); process.exitCode = 1 }).finally(async () => { await browser?.close() })
