import test from 'node:test'
import assert from 'node:assert/strict'
import {
  noiDungBuoiTap,
  noiDungKeHoach,
  soNhap,
  taoYeuCauGhi,
} from '../src/utils/tapLuyen.js'

test('Nhật ký dùng ID snapshot, không đưa chỉ tiêu dự kiến thành số hiệp thực tế', () => {
  const ban = {
    ghi_chu: '',
    bai_tap: [
      {
        id: 45,
        bai_tap_id: 7,
        noi_dung: { du_kien: { so_hiep: 4, muc_ta_kg: 80 } },
        hiep_tap: [],
      },
    ],
  }
  assert.deepEqual(noiDungBuoiTap(ban, '2026-10-05 01:02:03.000001'), {
    updated_at: '2026-10-05 01:02:03.000001',
    ghi_chu: null,
    bai_tap: [{ id: 45, hiep_tap: [] }],
  })
})
test('Mức tạ chưa biết là null, mức tạ 0 được giữ; nghỉ không được tự điền', () => {
  const ban = {
    bai_tap: [
      {
        id: 1,
        hiep_tap: [
          { so_lan_lap: '12', khoi_luong_kg: '', nghi_giay: '0' },
          { so_lan_lap: '8', khoi_luong_kg: '0', nghi_giay: '60' },
        ],
      },
    ],
  }
  const d = noiDungBuoiTap(ban, 'v')
  assert.equal(d.bai_tap[0].hiep_tap[0].khoi_luong_kg, null)
  assert.equal(d.bai_tap[0].hiep_tap[1].khoi_luong_kg, 0)
  ban.bai_tap[0].hiep_tap[0].nghi_giay = ''
  assert.throws(() => noiDungBuoiTap(ban, 'v'), /Bài 1, hiệp 1/)
})
test('Không biến chuỗi trống, số âm, số vô hạn hay phân số hiệp thành số hợp lệ', () => {
  for (const s of ['', '-1', 'Infinity', '2.3'])
    assert.throws(() => soNhap(s, 0, 100, true))
  assert.throws(() => soNhap('2.345', 0, 100))
  assert.equal(soNhap('12,5', 0, 100), 12.5)
})
test('Giáo án đánh số thứ tự liên tục riêng cho mỗi ngày và chỉ gửi các trường được phép', () => {
  const b = {
    bai_tap_id: 4,
    ngay_thu: '1',
    so_hiep: '3',
    so_lan_lap: '12',
    nghi_giay: '60',
    muc_ta_kg: '',
    ghi_chu: '',
    id: 999,
    ten_bai_tap: 'A',
  }
  const d = noiDungKeHoach({
    ten_ke_hoach: ' A ',
    muc_tieu: '',
    so_ngay_tap: '2',
    bai_tap: [b, { ...b, ngay_thu: '2' }, b],
  })
  assert.deepEqual(
    d.bai_tap.map((x) => x.thu_tu),
    [1, 1, 2],
  )
  assert.equal(d.ten_ke_hoach, 'A')
  assert.equal(d.giao_an_mau_id, null)
  assert.equal('id' in d.bai_tap[0], false)
})
test('Retry tạo/nhận xét giữ UUID và nội dung; chặn đổi nội dung khi chưa rõ kết quả', () => {
  let i = 0
  const q = taoYeuCauGhi(() => `uuid-${++i}`)
  const a = q.lay({ noi_dung: 'A' })
  assert.deepEqual(q.lay({ noi_dung: 'A' }), a)
  assert.throws(() => q.lay({ noi_dung: 'B' }), /chưa rõ kết quả/)
  q.xong()
  assert.equal(q.lay({ noi_dung: 'B' }).client_request_id, 'uuid-2')
})
