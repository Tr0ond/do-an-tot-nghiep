import test from 'node:test'
import assert from 'node:assert/strict'
import {
  doiNgay,
  ngayHopLe,
  gioMoKhung,
  gioVietNam,
  taoYeuCauDatLich,
} from '../src/utils/lich.js'

test('ngày/giờ Việt Nam không phụ thuộc múi giờ thiết bị và qua cuối tháng đúng', () => {
  assert.equal(doiNgay('2026-12-31', 1), '2027-01-01')
  assert.equal(ngayHopLe('2026-02-29'), false)
  assert.equal(ngayHopLe('2028-02-29'), true)
  assert.equal(gioMoKhung('2026-10-06', '08:30'), '2026-10-06T01:30:00.000Z')
  assert.equal(gioVietNam('2026-10-05T17:00:00Z'), '00:00')
  assert.throws(() => gioMoKhung('2026-10-06', '25:00'))
})
test('đặt lịch mất phản hồi dùng lại UUID; đổi slot tạo yêu cầu mới', async () => {
  const payloads = []
  let dem = 0
  const b = taoYeuCauDatLich({
    taoMa: () => `uuid-${++dem}`,
    goi: async (p) => {
      payloads.push(p)
      if (payloads.length === 1) throw new Error('Mất mạng')
      return { data: { id: 12 } }
    },
  })
  b.chon(5)
  await assert.rejects(b.gui(), /Mất mạng/)
  b.chon(5)
  await b.gui()
  assert.deepEqual(payloads[0], payloads[1])
  b.chon(6)
  await b.gui()
  assert.notEqual(payloads[1].client_request_id, payloads[2].client_request_id)
})
test('bấm liên tiếp không gửi hai yêu cầu hoặc đổi slot khi đang gửi', async () => {
  let xong
  const payloads = []
  const b = taoYeuCauDatLich({
    taoMa: () => 'uuid',
    goi: (p) => {
      payloads.push(p)
      return new Promise((r) => {
        xong = r
      })
    },
  })
  b.chon(5)
  const gui = b.gui()
  b.chon(6)
  assert.equal(await b.gui(), null)
  xong({ data: { id: 12 } })
  await gui
  assert.equal(payloads.length, 1)
  assert.equal(payloads[0].khung_gio_id, 5)
})
