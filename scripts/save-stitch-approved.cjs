const fs = require('node:fs')
const path = require('node:path')
const { createHash } = require('node:crypto')
const folder = path.resolve(__dirname, '../docs/design/stitch-approved')
const manifest = JSON.parse(fs.readFileSync(path.join(folder, 'manifest.json'), 'utf8'))
const escape = value => String(value).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c])

async function save() {
  const files = []
  for (const screen of manifest.screens) {
    const item = { id: screen.id, title: screen.title }
    for (const [field, extension] of [['htmlCode', 'html'], ['screenshot', 'png']]) {
      const target = path.join(folder, screen.id + '.' + extension)
      if (!fs.existsSync(target)) {
        const response = await fetch(screen[field].downloadUrl, { signal: AbortSignal.timeout(60000) })
        if (!response.ok) throw new Error(screen.title + ': HTTP ' + response.status)
        const bytes = Buffer.from(await response.arrayBuffer())
        if (extension === 'html' && !bytes.toString('utf8').includes('<html')) throw new Error('Not HTML: ' + screen.id)
        if (extension === 'png' && bytes.readUInt32BE(0) !== 0x89504e47) throw new Error('Not PNG: ' + screen.id)
        fs.writeFileSync(target, bytes, { flag: 'wx' })
      }
      const bytes = fs.readFileSync(target)
      item[extension] = { file: path.basename(target), bytes: bytes.length, sha256: createHash('sha256').update(bytes).digest('hex') }
    }
    files.push(item)
    console.log('Saved: ' + screen.title)
  }
  fs.writeFileSync(path.join(folder, 'files.json'), JSON.stringify({ savedAt: new Date().toISOString(), files }, null, 2))
  fs.writeFileSync(path.join(folder, 'index.html'), `<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mẫu Stitch đã duyệt</title><style>body{margin:0;background:#f5f7f8;color:#182325;font:15px/1.6 system-ui}header,main{max-width:1280px;margin:auto;padding:24px}h1{font-size:28px}main{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}article{background:white;border:1px solid #d6e0e2;border-radius:8px;overflow:hidden}img{width:100%;height:260px;object-fit:cover;object-position:top}h2{font-size:16px;margin:16px}a{color:#087f75}article a:last-child{display:block;padding:0 16px 20px}</style><header><h1>Mẫu Stitch đã duyệt</h1><p>32 mẫu gốc được lưu nguyên bản. Nội dung và số liệu trong mẫu là minh họa.</p></header><main>${files.map(s => `<article><a href="${s.png.file}"><img src="${s.png.file}" alt="${escape(s.title)}" loading="lazy"></a><h2>${escape(s.title)}</h2><a href="${s.html.file}">Mở HTML gốc</a></article>`).join('')}</main></html>`)
}
save().catch(error => { console.error(error.message); process.exitCode = 1 })
