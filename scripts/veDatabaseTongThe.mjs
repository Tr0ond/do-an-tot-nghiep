import fs from 'node:fs'
import path from 'node:path'
import assert from 'node:assert/strict'
import { fileURLToPath } from 'node:url'
import { cacBang } from './databaseSchema.mjs'

const goc = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const thoatXml = chuoi => String(chuoi).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;')
const cacHang = [
  ['nhom_co', 'bai_tap', 'giao_an_mau', 'bai_tap_trong_giao_an_mau', 'goi_tap', 'dang_ky_goi_tap', 'thanh_toan'],
  ['chi_so_co_the', 'ho_so_khach_hang', 'tai_khoan', 'ho_so_huan_luyen_vien', 'phan_cong_huan_luyen_vien', 'khung_gio_huan_luyen_vien', 'lich_hen_huan_luyen'],
  ['hiep_tap', 'bai_tap_trong_phien', 'phien_tap', 'lich_tap', 'ke_hoach_tap', 'bai_tap_trong_ke_hoach', 'ghi_chu_huan_luyen'],
  ['nhat_ky_he_thong', 'tai_lieu_tu_van', 'tin_nhan', 'hoi_thoai', 'hoi_thoai_tro_ly', 'yeu_cau_tro_ly', 'tin_nhan_tro_ly'],
]
const rongBang = 320
const caoHang = 26
const caoTieuDe = 32
const buocCot = 500
const le = 100
const cacViTri = new Map()
const cacKenh = []
let yHang = 130
for (const [hang, danhSach] of cacHang.entries()) {
  let caoNhat = 0
  danhSach.forEach((ten, cot) => {
    const bang = cacBang.find(bang => bang.ten === ten)
    assert.ok(bang)
    const cao = caoTieuDe + bang.cot.length * caoHang
    caoNhat = Math.max(caoNhat, cao)
    cacViTri.set(ten, { x: le + cot * buocCot, y: yHang, cao, hang, cot })
  })
  cacKenh.push(yHang + caoNhat + 55)
  yHang += caoNhat + 380
}
assert.equal(cacViTri.size, cacBang.length)
const rongCanvas = le * 2 + (cacHang[0].length - 1) * buocCot + rongBang
const caoCanvas = yHang + 100
const o = (id, giaTri, cha, kieu, x, y, rong, cao) => `<mxCell id="${id}" value="${thoatXml(giaTri)}" parent="${cha}" style="${kieu}" vertex="1"><mxGeometry x="${x}" y="${y}" width="${rong}" height="${cao}" as="geometry"/></mxCell>`
const coSo = 'html=1;fontFamily=Helvetica;fontColor=#000000;fontSize=12;strokeColor=#000000;fillColor=#ffffff;'
let noiDung = o('title', 'DATABASE — QUẢN LÝ HUẤN LUYỆN CÁ NHÂN', '1', `${coSo}strokeColor=none;fontSize=24;fontStyle=1;align=left;`, le, 35, 1800, 42)
noiDung += o('legend', 'PK: khóa chính    FK: khóa ngoại    ○: tùy chọn    chân quạ: nhiều    ·    28 bảng / 52 quan hệ FK', '1', `${coSo}strokeColor=none;align=left;fontSize=13;`, le, 82, 1800, 28)

for (const bang of cacBang) {
  const viTri = cacViTri.get(bang.ten)
  noiDung += o(`table-${bang.ten}`, bang.ten, '1', `${coSo}shape=table;startSize=${caoTieuDe};container=1;collapsible=0;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;`, viTri.x, viTri.y, rongBang, viTri.cao)
  bang.cot.forEach(([ten, , thamChieu], thuTu) => {
    const idHang = `row-${bang.ten}-${ten}`
    const khoa = thamChieu === 'PK' ? 'PK' : thamChieu ? 'FK' : ''
    const duongKe = thuTu === 0 ? 1 : 0
    noiDung += o(idHang, '', `table-${bang.ten}`, `${coSo}shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;collapsible=0;dropTarget=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom=${duongKe};`, 0, caoTieuDe + thuTu * caoHang, rongBang, caoHang)
    noiDung += o(`key-${bang.ten}-${ten}`, khoa, idHang, `${coSo}shape=partialRectangle;connectable=0;top=0;left=0;bottom=0;right=0;align=center;fontStyle=${khoa ? 1 : 0};overflow=hidden;whiteSpace=wrap;`, 0, 0, 38, caoHang)
    noiDung += o(`field-${bang.ten}-${ten}`, ten, idHang, `${coSo}shape=partialRectangle;connectable=0;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;fontStyle=${thamChieu === 'PK' ? 5 : 0};overflow=hidden;whiteSpace=wrap;`, 38, 0, rongBang - 38, caoHang)
  })
}

const cacFk = cacBang.flatMap(bang => bang.cot.filter(cot => cot[2] && cot[2] !== 'PK').map(cot => ({ con: bang, cot, cha: cacBang.find(cha => cha.ten === cot[2]) })))
const doBac = new Map()
for (const fk of cacFk) doBac.set(fk.cha.ten, (doBac.get(fk.cha.ten) || 0) + 1)
const demCha = new Map()
const demKenh = new Map()
const demBen = new Map()
const cacDuong = []
const layKenhDoc = (viTri, ben) => {
  const key = `${viTri.cot}-${ben}`
  const so = demBen.get(key) || 0
  demBen.set(key, so + 1)
  // Mỗi đầu nối một làn trong khoảng trống giữa cột, tránh vẽ đè lên bảng.
  const lech = 15 + so * 4
  assert.ok(lech < 160, `Không đủ làn cho ${key}`)
  return ben === 'phai' ? viTri.x + rongBang + lech : viTri.x - lech
}
for (const [thuTu, fk] of cacFk.entries()) {
  const cha = cacViTri.get(fk.cha.ten)
  const con = cacViTri.get(fk.con.ten)
  const tuPhai = cha.x < con.x || (cha.x === con.x && thuTu % 2 === 0)
  const benCha = tuPhai ? 'phai' : 'trai'
  const benCon = cha.cot === con.cot ? benCha : tuPhai ? 'trai' : 'phai'
  const lanCha = layKenhDoc(cha, benCha)
  const lanCon = layKenhDoc(con, benCon)
  const thuTuCha = demCha.get(fk.cha.ten) || 0
  demCha.set(fk.cha.ten, thuTuCha + 1)
  const exitY = 0.2 + 0.6 * (thuTuCha + 1) / (doBac.get(fk.cha.ten) + 1)
  const yCha = cha.y + caoTieuDe + exitY * caoHang
  const thuTuCot = fk.con.cot.findIndex(cot => cot[0] === fk.cot[0])
  const yCon = con.y + caoTieuDe + (thuTuCot + 0.5) * caoHang
  const kenh = Math.min(cha.hang, con.hang)
  const soKenh = demKenh.get(kenh) || 0
  demKenh.set(kenh, soKenh + 1)
  const yKenh = cacKenh[kenh] + soKenh * 10
  const xCha = cha.x + (tuPhai ? rongBang : 0)
  const xCon = con.x + (benCon === 'phai' ? rongBang : 0)
  const ganNhau = cha.cot === con.cot || (cha.hang === con.hang && Math.abs(cha.cot - con.cot) === 1)
  const cacDiem = ganNhau
    ? [{ x: xCha, y: yCha }, { x: lanCha, y: yCha }, { x: lanCha, y: yCon }, { x: xCon, y: yCon }]
    : [{ x: xCha, y: yCha }, { x: lanCha, y: yCha }, { x: lanCha, y: yKenh }, { x: lanCon, y: yKenh }, { x: lanCon, y: yCon }, { x: xCon, y: yCon }]
  cacDuong.push({ cha: fk.cha.ten, con: fk.con.ten, cot: fk.cot[0], diem: cacDiem })
  const tuyChon = !fk.cot[1].includes('NOT NULL')
  const motMot = fk.con.rangBuoc.includes(`UNIQUE (${fk.cot[0]})`)
  const diemXml = cacDiem.slice(1, -1).map(diem => `<mxPoint x="${diem.x}" y="${diem.y}"/>`).join('')
  noiDung += `<mxCell id="fk-${thuTu}" value="" parent="1" source="row-${fk.cha.ten}-id" target="row-${fk.con.ten}-${fk.cot[0]}" edge="1" style="html=1;edgeStyle=segmentEdgeStyle;rounded=0;strokeColor=#000000;strokeWidth=1;startArrow=${tuyChon ? 'ERzeroToOne' : 'ERmandOne'};endArrow=${motMot ? 'ERzeroToOne' : 'ERzeroToMany'};exitX=${tuPhai ? 1 : 0};exitY=${exitY};entryX=${benCon === 'phai' ? 1 : 0};entryY=0.5;exitPerimeter=0;entryPerimeter=0;"><mxGeometry relative="1" as="geometry"><Array as="points">${diemXml}</Array></mxGeometry></mxCell>`
}

// Kiểm tra mọi đoạn nối không xuyên qua bảng, trừ đúng bảng ở hai đầu.
for (const duong of cacDuong) {
  for (let i = 0; i < duong.diem.length - 1; i++) {
    const a = duong.diem[i]
    const b = duong.diem[i + 1]
    assert.ok(a.x === b.x || a.y === b.y)
    for (const [ten, v] of cacViTri) {
      if ((i === 0 && ten === duong.cha) || (i === duong.diem.length - 2 && ten === duong.con)) continue
      const catNgang = a.y === b.y && a.y > v.y && a.y < v.y + v.cao && Math.max(a.x, b.x) > v.x && Math.min(a.x, b.x) < v.x + rongBang
      const catDoc = a.x === b.x && a.x > v.x && a.x < v.x + rongBang && Math.max(a.y, b.y) > v.y && Math.min(a.y, b.y) < v.y + v.cao
      assert.ok(!catNgang && !catDoc, `Đường ${duong.cha} → ${duong.con}.${duong.cot} xuyên bảng ${ten}`)
    }
  }
}
assert.equal(cacFk.length, 52)
const xml = `<mxfile host="app.diagrams.net"><diagram id="database-full" name="Database"><mxGraphModel grid="1" gridSize="10" page="0" pageWidth="${rongCanvas}" pageHeight="${caoCanvas}" background="#ffffff" connect="1" arrows="1" fold="1"><root><mxCell id="0"/><mxCell id="1" parent="0"/>${noiDung}</root></mxGraphModel></diagram></mxfile>`
const tep = path.join(goc, 'docs/diagrams/database-single.generated.drawio')
fs.mkdirSync(path.dirname(tep), { recursive: true })
fs.writeFileSync(tep, xml + '\n', 'utf8')
console.log(`Đã tạo 1 canvas: ${cacBang.length} bảng, ${cacFk.length} FK, đủ ${cacBang.reduce((tong, bang) => tong + bang.cot.length, 0)} cột. Không có đường nối xuyên bảng.`)
console.log(`Kích thước ${rongCanvas} × ${caoCanvas}; file ${tep}`)
