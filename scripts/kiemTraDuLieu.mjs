import fs from 'node:fs'
import path from 'node:path'
import crypto from 'node:crypto'
import assert from 'node:assert/strict'
import { fileURLToPath } from 'node:url'
import { cacBang } from './databaseSchema.mjs'

const goc = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const doc = ten => fs.readFileSync(path.join(goc, ten))
const docJson = ten => JSON.parse(doc(ten).toString('utf8'))
const bam = noiDung => crypto.createHash('sha256').update(noiDung).digest('hex')
const nguon = docJson('exercises-dataset/data/exercises.json')
const baiTap = docJson('BE/database/data/bai_tap.json')
const nhomCo = docJson('BE/database/data/nhom_co.json')
const media = docJson('BE/database/data/media-manifest.json')
const thongKe = docJson('BE/database/data/thong_ke.json')
const nhomTheoId = new Map(nhomCo.map(nhom => [nhom.id, nhom]))
const baiTheoMa = new Map(baiTap.map(bai => [bai.ma_nguon, bai]))
assert.equal(baiTap.length, nguon.length)
assert.equal(baiTheoMa.size, nguon.length)
assert.equal(new Set(baiTap.map(bai => bai.id)).size, baiTap.length)
assert.equal(thongKe.nguon_sha256, bam(doc('exercises-dataset/data/exercises.json')))
for (const banGoc of nguon) {
  const bai = baiTheoMa.get(banGoc.id)
  assert.equal(bai.ten_bai_tap, banGoc.name)
  assert.equal(bai.ten_tieng_viet, null)
  assert.equal(nhomTheoId.get(bai.nhom_co_id).ten_nguon, banGoc.target)
  assert.equal(bai.co_ho_tro_nguon, banGoc.muscle_group)
  assert.equal(bai.ghi_cong_media, banGoc.attribution)
  assert.deepEqual(bai.huong_dan, banGoc.instructions)
  assert.deepEqual(bai.cac_buoc, banGoc.instruction_steps)
  assert.deepEqual(bai.co_phu, banGoc.secondary_muscles)
  assert.ok(fs.existsSync(path.join(goc, 'BE/public', bai.anh_url)))
  assert.ok(fs.existsSync(path.join(goc, 'BE/public', bai.gif_url)))
}
assert.equal(media.length, nguon.length * 2)
for (const tep of media) {
  const dich = doc(tep.dich)
  assert.equal(bam(doc(tep.nguon)), tep.sha256)
  assert.equal(bam(dich), tep.sha256)
  assert.equal(dich.length, tep.bytes)
}
const schema = doc('BE/database/design/schema.mysql.sql').toString('utf8')
assert.equal((schema.match(/CREATE TABLE `/g) || []).length, cacBang.length)
assert.equal((schema.match(/ADD FOREIGN KEY/g) || []).length, thongKe.so_khoa_ngoai)
assert.ok(!schema.includes('ON DELETE CASCADE'))
const seed = doc('BE/database/data/bai_tap.seed.sql').toString('utf8')
assert.ok(seed.includes('START TRANSACTION;') && seed.endsWith('COMMIT;\n'))
assert.ok(!seed.includes('ON DUPLICATE KEY') && !seed.includes('TRUNCATE'))
assert.deepEqual(docJson('BE/database/design/schema.json'), cacBang)
const xml = doc('docs/diagrams/database.drawio').toString('utf8')
assert.equal((xml.match(/<diagram /g) || []).length, 1)
assert.equal((xml.match(/edge="1"/g) || []).length, thongKe.so_khoa_ngoai)
for (const bang of cacBang) {
  assert.ok(xml.includes(`id="table-${bang.ten}"`))
  for (const [ten] of bang.cot) assert.ok(xml.includes(`id="field-${bang.ten}-${ten}"`))
}
assert.equal((doc('docs/diagrams/database-chi-tiet.drawio').toString('utf8').match(/<diagram /g) || []).length, 29)
for (const ten of ['database-overview.png', 'database-exercises.png', 'database-booking.png', 'database-full.png']) {
  assert.equal(doc(`docs/diagrams/${ten}`).subarray(0, 8).toString('hex'), '89504e470d0a1a0a')
}
console.log(`Đạt: ${baiTap.length} bài, ${nhomCo.length} nhóm, ${media.length} media nguyên bản, ${cacBang.length} bảng, ${thongKe.so_khoa_ngoai} FK, 1 canvas đủ cột và 4 PNG hợp lệ. Bản chi tiết 29 tab được giữ riêng.`)
console.log('Đây là kiểm tra dữ liệu/tệp; chưa chạy SQL hoặc test ứng dụng/MySQL.')
