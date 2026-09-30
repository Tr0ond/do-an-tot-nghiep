import fs from 'node:fs'
import path from 'node:path'
import assert from 'node:assert/strict'
import { fileURLToPath } from 'node:url'

// Đối chiếu tĩnh với thiết kế; không giả lập Laravel hoặc thực thi SQL/MySQL.
const goc = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const thuMuc = path.join(goc, 'BE/database/migrations')
const schema = JSON.parse(fs.readFileSync(path.join(goc, 'BE/database/design/schema.json'), 'utf8'))
// Các bảng kỹ thuật Laravel được quản lý riêng với 28 bảng nghiệp vụ trong thiết kế.
const tep = fs.readdirSync(thuMuc).filter(t => /^2026_10_01_\d{6}_create_.+_table\.php$/.test(t)).sort()
assert.equal(tep.length, schema.length, 'Số migration phải khớp số bảng nghiệp vụ')
const chuanHoa = s => s.replace(/\s+/g, '').toUpperCase()
const giaiMaChuoi = s => s.replace(/\\'/g, "'").replace(/\\\\/g, '\\')
const chuoiPhp = /'((?:\\.|[^'\\])*)'/g
const layChuoi = s => [...s.matchAll(chuoiPhp)].map(m => giaiMaChuoi(m[1]))
const tenRangBuocToanCuc = new Set()
const daTao = new Set()
const cacKhoaNgoai = []
let soCot = 0
let soCheck = 0
let soUnique = 0
let soIndex = 0
let soGenerated = 0

for (const [viTri, tenTep] of tep.entries()) {
  const b = schema[viTri]
  const duLieu = fs.readFileSync(path.join(thuMuc, tenTep))
  const php = duLieu.toString('utf8')
  assert.ok(!php.includes('\uFFFD') && !php.startsWith('\uFEFF') && !php.includes('\r'), `${tenTep}: UTF-8 không BOM, LF`)
  assert.equal(php.match(/Schema::create\('([^']+)'/)[1], b.ten)
  assert.equal(php.match(/Schema::dropIfExists\('([^']+)'/)[1], b.ten)
  assert.ok(php.includes("$table->engine = 'InnoDB';") && php.includes("$table->collation = 'utf8mb4_unicode_ci';"))
  assert.ok(!/cascade|disableForeignKey|withoutForeignKey/i.test(php))

  const cot = []
  for (const m of php.matchAll(/^\s*\$table->(id|string|char|decimal|enum|dateTime|unsignedBigInteger|unsignedInteger|boolean|date|json|text|longText)\(([^\n]*);$/gm)) {
    const [, loai, thamSo] = m
    if (loai === 'id') {
      cot.push(['id', 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'])
      continue
    }
    const ten = layChuoi(thamSo)[0]
    let kieu
    if (loai === 'string' || loai === 'char') {
      const n = thamSo.match(/, (\d+)\)/)[1]
      kieu = `${loai === 'string' ? 'VARCHAR' : 'CHAR'}(${n})`
    } else if (loai === 'decimal') {
      const n = thamSo.match(/, (\d+), (\d+)\)/)
      assert.ok(thamSo.includes('->unsigned()'))
      kieu = `DECIMAL(${n[1]},${n[2]}) UNSIGNED`
    } else if (loai === 'enum') {
      kieu = 'ENUM(' + layChuoi(thamSo).slice(1).map(v => `'${v}'`).join(',') + ')'
    } else {
      kieu = { dateTime: 'DATETIME(6)', unsignedBigInteger: 'BIGINT UNSIGNED', unsignedInteger: 'INT UNSIGNED', boolean: 'BOOLEAN', date: 'DATE', json: 'JSON', text: 'TEXT', longText: 'LONGTEXT' }[loai]
      if (loai === 'dateTime') assert.ok(/, 6\)/.test(thamSo))
    }
    if (thamSo.includes('->storedAs(')) {
      kieu += ` GENERATED ALWAYS AS (${layChuoi(thamSo).at(-1)}) STORED`
      soGenerated++
    } else {
      kieu += thamSo.includes('->nullable()') ? ' NULL' : ' NOT NULL'
      const macDinh = thamSo.match(/->default\((false|\d+)\)/)
      if (macDinh) kieu += ' DEFAULT ' + macDinh[1].toUpperCase()
    }
    cot.push([ten, kieu])
  }
  assert.deepEqual(cot.map(([t, k]) => [t, chuanHoa(k)]), b.cot.map(([t, k]) => [t, chuanHoa(k)]), `${b.ten}: kiểu/độ dài/nullable/default/generated`)
  soCot += cot.length

  const rangBuoc = []
  const tenIndex = new Set()
  for (const m of php.matchAll(/\$table->(unique|index)\(\[([^\]]+)\], '([^']+)'\);/g)) {
    rangBuoc.push(`${m[1] === 'unique' ? 'UNIQUE' : 'INDEX'} (${layChuoi(m[2]).join(', ')})`)
    assert.ok(m[3].length <= 64 && !tenIndex.has(m[3]))
    tenIndex.add(m[3])
    if (m[1] === 'unique') soUnique++
    else soIndex++
  }
  for (const m of php.matchAll(/DB::statement\('((?:\\.|[^'\\])*)'\);/g)) {
    const sql = giaiMaChuoi(m[1])
    const check = sql.match(/^ALTER TABLE `([^`]+)` ADD CONSTRAINT `([^`]+)` (CHECK \(.+\))$/)
    assert.ok(check && check[1] === b.ten, sql)
    assert.ok(check[2].length <= 64 && !tenRangBuocToanCuc.has(check[2]))
    tenRangBuocToanCuc.add(check[2])
    rangBuoc.push(check[3])
    soCheck++
  }
  assert.deepEqual(rangBuoc.map(chuanHoa).sort(), b.rangBuoc.map(chuanHoa).sort(), `${b.ten}: UNIQUE/INDEX/CHECK`)

  const fks = []
  for (const m of php.matchAll(/\$table->foreign\('([^']+)', '([^']+)'\)\s*->references\('id'\)->on\('([^']+)'\)\s*->restrictOnDelete\(\)->restrictOnUpdate\(\);/g)) {
    const [, tenCot, tenFk, dich] = m
    assert.ok(tenFk.length <= 64 && !tenRangBuocToanCuc.has(tenFk))
    tenRangBuocToanCuc.add(tenFk)
    assert.ok(daTao.has(dich) || dich === b.ten, `${b.ten}: bảng cha ${dich} chưa được tạo`)
    fks.push([tenCot, dich])
    cacKhoaNgoai.push([b.ten, dich])
  }
  assert.deepEqual(fks, b.cot.filter(c => c[2] && c[2] !== 'PK').map(c => [c[0], c[2]]), `${b.ten}: FK/RESTRICT`)
  daTao.add(b.ten)
}

// Khi rollback theo thứ tự ngược, không còn bảng con tham chiếu bảng đang xóa.
for (const b of [...schema].reverse()) {
  assert.ok(!cacKhoaNgoai.some(([con, cha]) => cha === b.ten && con !== cha && daTao.has(con)), `Rollback ${b.ten} trước bảng con`)
  daTao.delete(b.ten)
}
console.log(`Đạt đối chiếu tĩnh: ${tep.length} migrations / ${soCot} cột / ${cacKhoaNgoai.length} FK RESTRICT / ${soUnique} UNIQUE / ${soIndex} INDEX / ${soCheck} CHECK / ${soGenerated} generated columns.`)
console.log('Thứ tự tạo/xóa theo quan hệ FK, tên ràng buộc <=64 ký tự và UTF-8/LF hợp lệ. Chưa thực thi migrate/rollback trên Laravel/MySQL.')
