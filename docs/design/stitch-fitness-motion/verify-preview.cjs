const { chromium } = require('C:/Users/xtung/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright')
const { writeFileSync } = require('node:fs')
const { pathToFileURL } = require('node:url')
const path = require('node:path')
const thuMuc = __dirname
const cacMau = ['kh-light','kh-dark','pt-light','pt-dark','admin-light','admin-dark','mobile-light','mobile-dark']
;(async () => {
  const browser = await chromium.launch({ headless:true,channel:'chrome' })
  const ketQua = { ngay:'2026-10-04', moiTruong:'Chromium / Playwright, file HTML cục bộ; dữ liệu minh họa', manHinh:[], trangXem:[] }
  try {
    for (const ten of cacMau) {
      const mobile = ten.startsWith('mobile')
      const page = await browser.newPage({ viewport:{ width:mobile ? 390 : 1280,height:mobile ? 844 : 900 },deviceScaleFactor:1 })
      const loi = []
      page.on('pageerror',e => loi.push(e.message))
      await page.goto(pathToFileURL(path.join(thuMuc,ten+'.html')).href,{ waitUntil:'networkidle',timeout:45000 })
      await page.evaluate(() => document.fonts.ready)
      const thongTin = await page.evaluate(() => ({
        rong:innerWidth,tranNgang:document.documentElement.scrollWidth > innerWidth,
        anh:[...document.images].map(anh => ({taiDuoc:anh.complete && anh.naturalWidth>0})),
        nen:getComputedStyle(document.body).backgroundColor,
        diemDanh:[...document.querySelectorAll('button')].some(nut => nut.textContent.includes('Điểm danh')),
        tieuDe:document.title
      }))
      if (mobile) await page.evaluate(() => {
        const nav = document.querySelector('nav.fixed')
        if (nav) nav.style.position = 'absolute'
      })
      await page.screenshot({ path:path.join(thuMuc,ten+'.png'),fullPage:true })
      ketQua.manHinh.push({ten,...thongTin,loiJavaScript:loi})
      console.log(JSON.stringify({ten,...thongTin,loiJavaScript:loi}))
      await page.close()
    }
    for (const rong of [390,768,1440]) {
      const page = await browser.newPage({viewport:{width:rong,height:900}})
      await page.goto(pathToFileURL(path.join(thuMuc,'index.html')).href)
      for (const mau of ['kh','pt','admin','mobile']) {
        await page.locator('[data-screen="'+mau+'"]').click()
        for (const theme of ['light','dark']) {
          await page.locator('[data-mode="'+theme+'"]').click()
          await page.locator('#screen').evaluate(anh => anh.decode())
          const kq = await page.evaluate(() => ({
            nen:document.documentElement.dataset.theme,
            anh:document.getElementById('screen').getAttribute('src'),
            tranNgang:document.documentElement.scrollWidth > innerWidth,
            anhTaiDuoc:document.getElementById('screen').naturalWidth>0
          }))
          if (kq.nen!==theme || kq.anh!==mau+'-'+theme+'.png' || kq.tranNgang || !kq.anhTaiDuoc) throw new Error('Trang xem lỗi: '+JSON.stringify(kq))
        }
      }
      ketQua.trangXem.push({rong,soLuotDoi:8,tranNgang:false,anhTaiDuoc:true})
      await page.screenshot({path:path.join(thuMuc,'gallery-'+rong+'.png')})
      await page.close()
    }
    writeFileSync(path.join(thuMuc,'verification.json'),JSON.stringify(ketQua,null,2)+'\n','utf8')
    if (ketQua.manHinh.some(kq => kq.tranNgang || kq.loiJavaScript.length || kq.anh.some(anh => !anh.taiDuoc))) process.exitCode=1
  } finally { await browser.close() }
})().catch(e => {console.error(e);process.exitCode=1})
