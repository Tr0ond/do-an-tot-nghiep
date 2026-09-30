import fs from 'node:fs'
import path from 'node:path'
import crypto from 'node:crypto'
import assert from 'node:assert/strict'
import { fileURLToPath } from 'node:url'
import { cacBang } from './databaseSchema.mjs'

const thuMucGoc = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const duongDan = ten => path.join(thuMucGoc, ten)
const bam = noiDung => crypto.createHash('sha256').update(noiDung).digest('hex')
const ghi = (ten, noiDung) => {
  fs.mkdirSync(path.dirname(duongDan(ten)), { recursive: true })
  fs.writeFileSync(duongDan(ten), noiDung, 'utf8')
}
const ghiJson = (ten, duLieu) => ghi(ten, JSON.stringify(duLieu, null, 2) + '\n')
const nguon = fs.readFileSync(duongDan('exercises-dataset/data/exercises.json'))
const duLieu = JSON.parse(nguon.toString('utf8')).sort((a, b) => a.id.localeCompare(b.id))
const tenNhom = {
  abs: 'Cơ bụng', quads: 'Cơ đùi trước', lats: 'Cơ xô', calves: 'Cơ bắp chân',
  pectorals: 'Cơ ngực', glutes: 'Cơ mông', hamstrings: 'Cơ đùi sau', adductors: 'Cơ khép đùi',
  triceps: 'Cơ tay sau', 'cardiovascular system': 'Tim mạch', spine: 'Cơ dọc cột sống',
  'upper back': 'Cơ lưng trên', biceps: 'Cơ tay trước', delts: 'Cơ vai', forearms: 'Cơ cẳng tay',
  traps: 'Cơ thang', 'serratus anterior': 'Cơ răng trước', abductors: 'Cơ dạng đùi',
  'levator scapulae': 'Cơ nâng vai',
}
const tenDungCu = {
  'body weight': 'Trọng lượng cơ thể', cable: 'Máy cáp', 'leverage machine': 'Máy tập đòn bẩy',
  assisted: 'Thiết bị trợ lực', 'medicine ball': 'Bóng tạ', 'stability ball': 'Bóng thăng bằng',
  band: 'Dây tập', barbell: 'Tạ đòn', rope: 'Dây thừng', dumbbell: 'Tạ đơn',
  'ez barbell': 'Tạ đòn EZ', 'sled machine': 'Máy trượt', 'upper body ergometer': 'Máy đạp tay',
  kettlebell: 'Tạ chuông', 'olympic barbell': 'Tạ đòn Olympic', weighted: 'Tạ bổ sung',
  'bosu ball': 'Bóng BOSU', 'resistance band': 'Dây kháng lực', roller: 'Con lăn',
  'skierg machine': 'Máy SkiErg', hammer: 'Búa tập', 'smith machine': 'Máy Smith',
  'wheel roller': 'Bánh xe tập bụng', 'stationary bike': 'Xe đạp tại chỗ', tire: 'Lốp xe',
  'trap bar': 'Thanh tạ Trap bar', 'elliptical machine': 'Máy Elliptical', 'stepmill machine': 'Máy leo cầu thang',
}
const cacNhom = [...new Set(duLieu.map(bai => bai.target))].sort().map((ten, viTri) => {
  assert.ok(tenNhom[ten], `Thiếu nhãn nhóm cơ: ${ten}`)
  return { id: viTri + 1, ma_nhom_co: ten.replaceAll(' ', '_'), ten_nhom_co: tenNhom[ten], ten_nguon: ten, trang_thai: 'HOAT_DONG' }
})
const maNhom = new Map(cacNhom.map(nhom => [nhom.ten_nguon, nhom.id]))
const cacMa = new Set()
const cacMedia = []
const chuyenNgay = ngay => {
  if (ngay == null) return null
  assert.ok(!Number.isNaN(Date.parse(ngay)), `Ngày không hợp lệ: ${ngay}`)
  return new Date(ngay).toISOString().slice(0, 23).replace('T', ' ')
}
const saoChepMedia = (thamChieu, loai) => {
  assert.match(thamChieu, /^(images|videos)\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif)$/)
  const tepNguon = duongDan(`exercises-dataset/${thamChieu}`)
  const noiDung = fs.readFileSync(tepNguon)
  if (loai === 'animations') {
    assert.ok(['GIF87a', 'GIF89a'].includes(noiDung.toString('ascii', 0, 6)))
    assert.equal(noiDung.readUInt16LE(6), 180)
    assert.equal(noiDung.readUInt16LE(8), 180)
  }
  const url = `/media/bai-tap/${loai}/${path.basename(thamChieu)}`
  const dich = duongDan(`BE/public${url}`)
  fs.mkdirSync(path.dirname(dich), { recursive: true })
  if (fs.existsSync(dich)) assert.equal(bam(fs.readFileSync(dich)), bam(noiDung), `Không ghi đè media đã thay đổi: ${dich}`)
  else fs.copyFileSync(tepNguon, dich)
  cacMedia.push({ nguon: `exercises-dataset/${thamChieu}`, dich: `BE/public${url}`, sha256: bam(noiDung), bytes: noiDung.length })
  return url
}
const cacBai = duLieu.map((bai, viTri) => {
  assert.match(bai.id, /^\d{4}$/)
  assert.ok(!cacMa.has(bai.id), `Trùng mã bài: ${bai.id}`)
  cacMa.add(bai.id)
  assert.ok(bai.name && bai.name.length <= 255)
  assert.ok(bai.instructions.en && bai.instruction_steps.en.length)
  assert.ok(tenDungCu[bai.equipment], `Thiếu nhãn dụng cụ: ${bai.equipment}`)
  assert.ok(bai.attribution)
  // target là nhóm cơ chính; muscle_group trong nguồn là cơ hỗ trợ.
  return {
    id: viTri + 1, nhom_co_id: maNhom.get(bai.target), nguon_du_lieu: 'exercises-dataset',
    ma_nguon: bai.id, ten_bai_tap: bai.name, ten_tieng_viet: null, bo_phan_co_the: bai.body_part,
    dung_cu: tenDungCu[bai.equipment], dung_cu_nguon: bai.equipment,
    huong_dan: bai.instructions, cac_buoc: bai.instruction_steps, co_phu: bai.secondary_muscles,
    co_ho_tro_nguon: bai.muscle_group,
    anh_url: saoChepMedia(bai.image, 'images'), gif_url: saoChepMedia(bai.gif_url, 'animations'),
    duong_dan_anh_nguon: `exercises-dataset/${bai.image}`, duong_dan_gif_nguon: `exercises-dataset/${bai.gif_url}`,
    ma_media_nguon: bai.media_id, ghi_cong_media: bai.attribution,
    nguon_tao_luc: chuyenNgay(bai.created_at), nguon_cap_nhat_luc: chuyenNgay(bai.updated_at), trang_thai: 'HOAT_DONG',
  }
})

assert.equal(cacBang.length, 28)
const tenBang = new Set(cacBang.map(bang => bang.ten))
for (const bang of cacBang) {
  assert.equal(new Set(bang.cot.map(cot => cot[0])).size, bang.cot.length)
  for (const [, , thamChieu] of bang.cot) if (thamChieu && thamChieu !== 'PK') assert.ok(tenBang.has(thamChieu), `FK không tồn tại: ${thamChieu}`)
}
const taoBang = bang => {
  const cacCot = bang.cot.map(([ten, kieu]) => `  \`${ten}\` ${kieu}`)
  const rangBuoc = ['PRIMARY KEY (id)', ...bang.rangBuoc]
  // FK được thêm sau toàn bộ CREATE để cả tham chiếu ngược/self-reference hợp lệ.
  return `CREATE TABLE \`${bang.ten}\` (\n${[...cacCot, ...rangBuoc.map(cau => `  ${cau}`)].join(',\n')}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;`
}
const khoaNgoai = cacBang.flatMap(bang => bang.cot.filter(cot => cot[2] && cot[2] !== 'PK').map(([ten, , thamChieu]) => `ALTER TABLE \`${bang.ten}\` ADD FOREIGN KEY (\`${ten}\`) REFERENCES \`${thamChieu}\` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;`))
ghi('BE/database/design/schema.mysql.sql', `-- Thiết kế MySQL 8.0.16+; chưa phải migration Laravel hoặc bằng chứng chạy DB.\n-- Chạy trên database mới đã chọn. Không DROP, không tự xóa dữ liệu. Thời gian lưu UTC.\nSET NAMES utf8mb4;\nSET time_zone = '+00:00';\n\n${cacBang.map(taoBang).join('\n\n')}\n\n${khoaNgoai.join('\n')}\n`)

const giaTriSql = giaTri => {
  if (giaTri === null) return 'NULL'
  if (typeof giaTri === 'number') return String(giaTri)
  const chuoi = typeof giaTri === 'object' ? JSON.stringify(giaTri) : giaTri
  // Chuỗi hex UTF-8 giữ nguyên JSON và không phụ thuộc NO_BACKSLASH_ESCAPES.
  return `CONVERT(0x${Buffer.from(chuoi, 'utf8').toString('hex')} USING utf8mb4)`
}
const chen = (ten, danhSach) => {
  const cot = Object.keys(danhSach[0])
  const cacLenh = []
  for (let viTri = 0; viTri < danhSach.length; viTri += 50) {
    cacLenh.push(`INSERT INTO \`${ten}\` (${[...cot.map(ten => `\`${ten}\``), 'created_at', 'updated_at'].join(', ')}) VALUES\n${danhSach.slice(viTri, viTri + 50).map(banGhi => `(${[...cot.map(ten => giaTriSql(banGhi[ten])), 'UTC_TIMESTAMP(6)', 'UTC_TIMESTAMP(6)'].join(', ')})`).join(',\n')};`)
  }
  return cacLenh.join('\n\n')
}
ghi('BE/database/data/bai_tap.seed.sql', `-- Chỉ nhập một lần vào hai bảng nhom_co/bai_tap trống sau schema.mysql.sql.\n-- Không UPSERT: chạy lại bị unique constraint chặn, không ghi đè chỉnh sửa của Admin.\nSET NAMES utf8mb4;\nSET time_zone = '+00:00';\nSTART TRANSACTION;\n${chen('nhom_co', cacNhom)}\n\n${chen('bai_tap', cacBai)}\nCOMMIT;\n`)
ghiJson('BE/database/data/nhom_co.json', cacNhom)
ghiJson('BE/database/data/bai_tap.json', cacBai)
ghiJson('BE/database/data/media-manifest.json', cacMedia)
for (const ten of ['LICENSE', 'NOTICE.md']) ghi(`BE/database/data/${ten}`, fs.readFileSync(duongDan(`exercises-dataset/${ten}`), 'utf8').replaceAll('\r\n', '\n'))
const thongKe = {
  so_bai_tap: cacBai.length, so_nhom_co: cacNhom.length, so_dung_cu: Object.keys(tenDungCu).length,
  so_anh: cacMedia.filter(media => media.dich.includes('/images/')).length,
  so_gif: cacMedia.filter(media => media.dich.includes('/animations/')).length,
  tong_bytes_media: cacMedia.reduce((tong, media) => tong + media.bytes, 0),
  so_bang: cacBang.length, so_khoa_ngoai: khoaNgoai.length,
  nguon_sha256: bam(nguon), ngon_ngu: Object.keys(cacBai[0].huong_dan),
  so_bai_co_ten_tieng_viet: 0,
  quyen_media: 'Chủ dự án xác nhận có quyền sử dụng ảnh/GIF ngày 01/10/2026; giữ ghi công nguồn, không đổi kích thước.',
}
ghiJson('BE/database/data/thong_ke.json', thongKe)

const xmlEscape = chuoi => String(chuoi).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;')
const o = (id, noiDung, x, y, rong, cao, kieu = '') => `<mxCell id="${id}" value="${xmlEscape(noiDung)}" style="html=1;whiteSpace=wrap;rounded=0;fillColor=#ffffff;strokeColor=#cbd5e1;fontFamily=Helvetica;${kieu}" vertex="1" parent="1"><mxGeometry x="${x}" y="${y}" width="${rong}" height="${cao}" as="geometry"/></mxCell>`
const khung = noiDung => `<mxGraphModel dx="800" dy="600" background="#ffffff" page="1" pageWidth="800" pageHeight="600"><root><mxCell id="0"/><mxCell id="1" parent="0"/>${noiDung}</root></mxGraphModel>`
const tieuDe = ten => o('title', `<b>${ten}</b>`, 40, 20, 720, 32, 'strokeColor=none;fontSize=18;align=left;')
const ghiChu = noiDung => o('note', noiDung, 40, 560, 720, 32, 'strokeColor=none;fontSize=11;align=left;fontColor=#475569;')
const mau = ['#166534', '#0369a1', '#b45309', '#7e22ce']
const chiTiet = cacBang.map((bang, chiSo) => {
  let noiDung = tieuDe(`T${String(chiSo + 1).padStart(2, '0')} · ${bang.ten}`)
  const cao = Math.max(180, 54 + bang.cot.length * 15)
  const hang = bang.cot.map(([ten, kieu, thamChieu]) => {
    const nhan = thamChieu === 'PK' ? '<b>PK</b>' : thamChieu ? '<b>FK</b>' : '&nbsp;&nbsp;'
    const loai = kieu.includes('GENERATED') ? 'generated' : kieu.startsWith('BIGINT') ? 'bigint' : kieu.startsWith('ENUM') ? 'enum' : kieu.split(' ')[0].toLowerCase()
    return `<div style="line-height:15px">${nhan} ${ten} <font color="#64748b">${loai}</font></div>`
  })
  noiDung += o(`t-${bang.ten}`, `<div style="font-size:12px;color:${mau[chiSo % 4]}"><b>${bang.ten}</b></div><hr>${hang.join('')}`, 360, 76, 400, cao, 'align=left;verticalAlign=top;spacing=10;fontSize=11;')
  const cacFk = bang.cot.filter(cot => cot[2] && cot[2] !== 'PK')
  cacFk.forEach(([ten, kieu, cha], viTri) => {
    const y = 80 + viTri * 58
    const vaoY = (y + 22 - 76) / cao
    const motMot = bang.rangBuoc.includes(`UNIQUE (${ten})`)
    noiDung += o(`ref-${viTri}`, `<b>${cha}</b><br><font color="#64748b">${ten} → id</font>`, 40, y, 248, 44, 'fontSize=11;align=left;spacing=6;')
    noiDung += `<mxCell id="e-${viTri}" value="" edge="1" parent="1" source="ref-${viTri}" target="t-${bang.ten}" style="edgeStyle=orthogonalEdgeStyle;html=1;startArrow=${kieu.endsWith('NULL') && !kieu.endsWith('NOT NULL') ? 'ERzeroToOne' : 'ERone'};endArrow=${motMot ? 'ERzeroToOne' : 'ERzeroToMany'};exitX=1;exitY=0.5;entryX=0;entryY=${Math.min(0.95, vaoY)};strokeColor=#64748b;"><mxGeometry relative="1" as="geometry"/></mxCell>`
  })
  noiDung += ghiChu('Bên trái: bảng được tham chiếu (không phải bảng mới). PK/FK và toàn bộ cột ở bên phải. UNIQUE/CHECK/NULL chi tiết trong SQL.')
  return `<diagram id="table-${bang.ten}" name="T${String(chiSo + 1).padStart(2, '0')} ${xmlEscape(bang.ten)}">${khung(noiDung)}</diagram>`
})
// Tổng quan hiển thị đủ 28 bảng; quan hệ chi tiết nằm ở từng tab và SQL.
let tongQuan = tieuDe('DATABASE · HUẤN LUYỆN CÁ NHÂN')
const nhomTongQuan = [
  ['Tài khoản', ['tai_khoan', 'ho_so_khach_hang', 'ho_so_huan_luyen_vien', 'chi_so_co_the']],
  ['Gói / payOS', ['goi_tap', 'dang_ky_goi_tap', 'thanh_toan']],
  ['PT / lịch hẹn', ['phan_cong_huan_luyen_vien', 'khung_gio_huan_luyen_vien', 'lich_hen_huan_luyen']],
  ['Danh mục', ['nhom_co', 'bai_tap', 'giao_an_mau', 'bai_tap_trong_giao_an_mau']],
  ['Kế hoạch / lịch tự tập', ['ke_hoach_tap', 'bai_tap_trong_ke_hoach', 'lich_tap']],
  ['Kết quả tập', ['phien_tap', 'bai_tap_trong_phien', 'hiep_tap', 'ghi_chu_huan_luyen']],
  ['Chat PT', ['hoi_thoai', 'tin_nhan']],
  ['Chatbot', ['hoi_thoai_tro_ly', 'yeu_cau_tro_ly', 'tin_nhan_tro_ly']],
  ['Quản trị nội dung', ['tai_lieu_tu_van', 'nhat_ky_he_thong']],
]
nhomTongQuan.forEach(([ten, bang], chiSo) => {
  tongQuan += o(`g-${chiSo}`, `<b>${ten}</b><hr>${bang.join('<br>')}`, 40 + (chiSo % 3) * 248, 76 + Math.floor(chiSo / 3) * 154, 224, 134, 'align=left;spacing=8;verticalAlign=top;fontSize=11;')
})
tongQuan += ghiChu('28 bảng nghiệp vụ · 52 FK · InnoDB / utf8mb4 · UTC. Mỗi bảng một tab chi tiết; không gồm bảng kỹ thuật Laravel.')
const xml = `<mxfile host="app.diagrams.net"><diagram id="overview" name="Tổng quan 28 bảng">${khung(tongQuan)}</diagram>${chiTiet.join('')}</mxfile>`
ghi('docs/diagrams/database.generated.drawio', xml)
ghiJson('BE/database/design/schema.json', cacBang)
ghi('docs/DATABASE_DICTIONARY.md', `# Từ điển dữ liệu\n\nSinh từ \`scripts/databaseSchema.mjs\`. Đầy đủ 28 bảng/52 FK; sơ đồ tại [database.drawio](diagrams/database.drawio). SQL chưa chạy trên MySQL.\n\n${cacBang.map(bang => `## ${bang.ten}\n\n| Cột | Kiểu SQL | Khóa/tham chiếu |\n| --- | --- | --- |\n${bang.cot.map(([ten, kieu, thamChieu]) => `| \`${ten}\` | \`${kieu}\` | ${thamChieu || ''} |`).join('\n')}\n\nRàng buộc: ${bang.rangBuoc.map(cau => `\`${cau}\``).join('; ') || 'PK và FK'}\n`).join('\n')}`)
console.log(JSON.stringify(thongKe, null, 2))
