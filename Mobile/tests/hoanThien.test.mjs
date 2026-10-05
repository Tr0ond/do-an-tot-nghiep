import test from 'node:test'
import assert from 'node:assert/strict'
import {
  linkPayosHopLe,
  conChoThanhToan,
  kiemTraTaiKhoan,
  layMaKhoiPhuc,
  soanYeuCauAi,
  maDaHoanTat,
} from '../src/utils/hoanThien.js'
import { taoYeuCauGhi } from '../src/utils/tapLuyen.js'
import { dichThongBao } from '../src/utils/traoDoi.js'

test('Thanh toán chỉ mở HTTPS pay.payos.vn, không có credentials/port/host giả', () => {
  assert.equal(linkPayosHopLe('https://pay.payos.vn/web/123'), true)
  for (const u of [
    'javascript:alert(1)',
    'http://pay.payos.vn/1',
    'https://pay.payos.vn.evil.test',
    'https://evil.test',
    'https://x:pw@pay.payos.vn',
    'https://pay.payos.vn:444',
    '//pay.payos.vn',
  ])
    assert.equal(linkPayosHopLe(u), false)
  const d = {
    trang_thai: 'CHO_THANH_TOAN',
    han_thanh_toan: '2026-10-05T00:15:00Z',
  }
  assert.equal(conChoThanhToan(d, Date.parse(d.han_thanh_toan) - 1), true)
  assert.equal(conChoThanhToan(d, Date.parse(d.han_thanh_toan)), false)
  assert.equal(conChoThanhToan({ ...d, trang_thai: 'DANG_SU_DUNG' }, 0), false)
})
test('Retry đơn giữ UUID và giá trị, chưa rõ kết quả không đổi gói', () => {
  const q = taoYeuCauGhi(() => 'a')
  assert.equal(q.coCho(), false)
  const d = q.lay({ goi_tap_id: 5 })
  assert.equal(q.coCho(), true)
  assert.deepEqual(q.lay({ goi_tap_id: 5 }), d)
  assert.throws(() => q.lay({ goi_tap_id: 6 }))
  q.xong()
  assert.equal(q.coCho(), false)
})
test('Mật khẩu có dấu kiểm tra byte bcrypt, xác nhận và email', () => {
  const d = {
    ho_ten: 'An',
    email: 'an@example.test',
    password: '12345678',
    password_confirmation: '12345678',
  }
  assert.deepEqual(kiemTraTaiKhoan(d, true), {})
  assert.ok(kiemTraTaiKhoan({ ...d, password: 'ấ'.repeat(25) }, true).password)
  assert.ok(kiemTraTaiKhoan({ ...d, email: 'x', ho_ten: '' }, true).email)
  assert.ok(
    kiemTraTaiKhoan({ ...d, password_confirmation: 'x' }, true)
      .password_confirmation,
  )
})
test('Reset chỉ đọc mã hợp lệ hoặc fragment web, không dùng query/scheme lạ', () => {
  const ma = 'a'.repeat(64)
  assert.equal(layMaKhoiPhuc(ma), ma)
  assert.equal(
    layMaKhoiPhuc(
      `https://example.test/dat-lai-mat-khau#token=${ma}&email=an%40example.test`,
    ),
    ma,
  )
  for (const u of [
    'a'.repeat(63),
    `https://example.test/dat-lai-mat-khau?token=${ma}`,
    `tr0ond://dat-lai-mat-khau#token=${ma}`,
    `https://example.test/khac#token=${ma}`,
  ])
    assert.equal(layMaKhoiPhuc(u), '')
})
test('Yêu cầu giáo án AI không vượt 30 buổi/120 bài, chỉ soạn câu hỏi', () => {
  assert.match(
    soanYeuCauAi(3, 4, 4),
    /3 buổi\/tuần trong 4 tuần, mỗi buổi 4 bài/,
  )
  for (const d of [
    [8, 1, 4],
    [3, 11, 1],
    [3, 4, 11],
    [4, 4, 8],
    [1.5, 2, 3],
    [0, 1, 1],
  ])
    assert.throws(() => soanYeuCauAi(...d))
})
test('Mất phản hồi AI chỉ nhận đúng UUID đã thành công, không bỏ yêu cầu khác', () => {
  const tin = [
    { client_request_id: 'a', trang_thai: 'LOI' },
    { client_request_id: 'b', trang_thai: 'THANH_CONG' },
  ]
  assert.equal(maDaHoanTat(tin, 'a'), false)
  assert.equal(maDaHoanTat(tin, 'b'), true)
})
test('Thông báo thanh toán chỉ mở đơn KH đúng ID, không cấp trạng thái từ URL', () => {
  assert.deepEqual(dichThongBao('/khach-hang/don-hang/12', 'KHACH_HANG'), {
    name: 'ChiTietDon',
    params: { id: 12 },
  })
  for (const p of [
    '/khach-hang/don-hang/12?paid=true',
    '/pt/don-hang/12',
    '/khach-hang/don-hang/0',
  ])
    assert.equal(dichThongBao(p, 'HUAN_LUYEN_VIEN'), null)
})
