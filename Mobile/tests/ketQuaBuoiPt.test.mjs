import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'
import {
  banNhapKetQuaPt,
  noiDungKetQuaPt,
  duHiepDeChotPt,
  taoLanGuiKetQuaPt,
} from '../src/utils/ketQuaBuoiPt.js'

const phienBan = '2026-10-06 02:10:12.123456'
const ban = () => ({
  ghi_chu: ' Ghi chú ',
  nhan_xet: ' Tốt ',
  bai_tap: [
    {
      bai_tap_id: 3,
      ten_bai_tap: 'Không gửi snapshot',
      anh_url: '/anh',
      hiep_tap: [{ so_lan_lap: '10', khoi_luong_kg: '', nghi_giay: '0' }],
    },
  ],
})

test('PT: payload whitelist, micro giây nguyên vẹn; tạ chưa ghi khác 0 và hỗ trợ dấu phẩy Android', () => {
  const d = ban()
  assert.deepEqual(noiDungKetQuaPt(d, phienBan), {
    updated_at: phienBan,
    ghi_chu: 'Ghi chú',
    nhan_xet: 'Tốt',
    bai_tap: [
      {
        bai_tap_id: 3,
        hiep_tap: [{ so_lan_lap: 10, khoi_luong_kg: null, nghi_giay: 0 }],
      },
    ],
  })
  d.bai_tap[0].hiep_tap[0].khoi_luong_kg = '0'
  assert.equal(noiDungKetQuaPt(d, null).bai_tap[0].hiep_tap[0].khoi_luong_kg, 0)
  d.bai_tap[0].hiep_tap[0].khoi_luong_kg = '7,50'
  assert.equal(
    noiDungKetQuaPt(d, null).bai_tap[0].hiep_tap[0].khoi_luong_kg,
    7.5,
  )
})

test('PT: loại bỏ số không hợp lệ/ngoài giới hạn, bài trùng, quá số bài/hiệp/ghi chú', () => {
  for (const [k, v] of [
    ['so_lan_lap', '0'],
    ['so_lan_lap', '1001'],
    ['so_lan_lap', '1.5'],
    ['nghi_giay', ''],
    ['nghi_giay', '-1'],
    ['nghi_giay', '3601'],
    ['khoi_luong_kg', '7.555'],
    ['khoi_luong_kg', '1001'],
    ['khoi_luong_kg', 'NaN'],
    ['khoi_luong_kg', '-1'],
  ]) {
    const d = ban()
    d.bai_tap[0].hiep_tap[0][k] = v
    assert.throws(() => noiDungKetQuaPt(d, phienBan), /Bài 1, hiệp 1/)
  }
  const d = ban()
  d.bai_tap.push(d.bai_tap[0])
  assert.throws(() => noiDungKetQuaPt(d, null), /trùng/)
  d.bai_tap = Array(31).fill(d.bai_tap[0])
  assert.throws(() => noiDungKetQuaPt(d, null), /30 bài/)
  d.bai_tap = [ban().bai_tap[0]]
  d.bai_tap[0].hiep_tap = Array(21).fill(d.bai_tap[0].hiep_tap[0])
  assert.throws(() => noiDungKetQuaPt(d, null), /20 hiệp/)
  assert.throws(
    () => noiDungKetQuaPt({ ...ban(), ghi_chu: 'a'.repeat(2001) }, null),
    /2.000/,
  )
})

test('PT: nháp rỗng hợp lệ, chốt cần bài/hiệp; snapshot đọc giữ đủ trường và không làm biến đổi response', () => {
  const d = banNhapKetQuaPt(null)
  assert.equal(duHiepDeChotPt(d), false)
  assert.deepEqual(noiDungKetQuaPt(d, null).bai_tap, [])
  const goc = {
    ghi_chu: null,
    bai_tap: [
      {
        bai_tap_id: 3,
        ten_bai_tap: 'Tên lịch sử',
        anh_url: '/media/bai-tap/3/anh',
        hiep_tap: [{ so_lan_lap: 12, khoi_luong_kg: '0.00', nghi_giay: 0 }],
      },
    ],
  }
  const moi = banNhapKetQuaPt(goc)
  assert.equal(moi.bai_tap[0].ten_bai_tap, 'Tên lịch sử')
  assert.equal(duHiepDeChotPt(moi), true)
  moi.bai_tap[0].hiep_tap[0].so_lan_lap = '20'
  assert.equal(goc.bai_tap[0].hiep_tap[0].so_lan_lap, 12)
})

test('PT: mất phản hồi giữ payload; retry bất chấp form bị sửa, thành công giải phóng ý định', async () => {
  const gui = taoLanGuiKetQuaPt()
  const d = ban()
  let lanDau
  await assert.rejects(
    gui.gui(
      'luu',
      () => noiDungKetQuaPt(d, phienBan),
      async (p) => {
        lanDau = p
        throw new Error('timeout')
      },
    ),
  )
  d.bai_tap[0].hiep_tap[0].so_lan_lap = '99'
  await assert.rejects(
    gui.gui(
      'chot',
      () => ({}),
      async () => ({}),
    ),
    /đang chờ/,
  )
  await gui.gui(
    'luu',
    () => noiDungKetQuaPt(d, 'khác'),
    async (p) => {
      assert.deepEqual(p, lanDau)
      return { status: true }
    },
  )
  assert.equal(gui.lay(), null)
})

test('PT: double submit chỉ một request; 409/422 giải phóng retry, 5xx và 2xx sai envelope giữ chốt', async () => {
  const gui = taoLanGuiKetQuaPt()
  let xong
  const p = gui.gui(
    'chot',
    () => ({ updated_at: phienBan }),
    () =>
      new Promise((r) => {
        xong = r
      }),
  )
  assert.equal(
    await gui.gui(
      'chot',
      () => ({}),
      () => {
        throw new Error('không gọi')
      },
    ),
    null,
  )
  xong({ status: true })
  await p
  for (const status of [500, 200, 409, 422]) {
    await assert.rejects(
      gui.gui(
        'chot',
        () => ({ updated_at: phienBan }),
        async () => {
          throw Object.assign(new Error('lỗi'), { status })
        },
      ),
    )
    assert.equal(!!gui.lay(), status === 500 || status === 200)
  }
})

test('PT: lỗi validation trước khi gửi không giữ pending', async () => {
  const gui = taoLanGuiKetQuaPt()
  await assert.rejects(
    gui.gui(
      'luu',
      () => {
        throw new Error('validation')
      },
      () => {
        throw new Error('không gọi')
      },
    ),
    /validation/,
  )
  assert.equal(gui.lay(), null)
})

test('PT service: đúng role endpoint, bearer, PUT nháp và POST chốt giữ phiên bản', async () => {
  const source = fs
    .readFileSync(
      new URL('../src/services/ketQuaBuoiPtService.js', import.meta.url),
      'utf8',
    )
    .replace(
      "import { goiApi } from '../utils/http'",
      'const goiApi = (...args) => globalThis.__kqptApi(...args)',
    )
  const { ketQuaBuoiPtService: api } = await import(
    `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
  )
  const ds = []
  globalThis.__kqptApi = (...args) => ds.push(args)
  try {
    const signal = new AbortController().signal
    api.tai('kh', 'KHACH_HANG', 5, signal)
    api.tai('pt', 'HUAN_LUYEN_VIEN', 5, signal)
    api.luu('pt', 5, noiDungKetQuaPt(ban(), phienBan))
    api.chot('pt', 5, { updated_at: phienBan })
    assert.equal(ds[0][0], '/khach-hang/lich-hen/5/ket-qua')
    assert.equal(ds[0][1].signal, signal)
    assert.equal(ds[1][0], '/pt/lich-hen/5/ket-qua')
    assert.equal(ds[2][1].method, 'PUT')
    assert.equal(ds[3][1].method, 'POST')
    assert.equal(ds[3][0], '/pt/lich-hen/5/ket-qua/chot')
    assert.equal(ds[3][1].duLieu.updated_at, phienBan)
    assert.equal(ds[3][1].token, 'pt')
    assert.equal(ds[3][1].dayDu, true)
  } finally {
    delete globalThis.__kqptApi
  }
})
