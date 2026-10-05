import test from 'node:test'
import assert from 'node:assert/strict'
import {
  gopTin,
  taiTinMoi,
  taoTinCho,
  kiemTraTin,
  anhDataUri,
  dichThongBao,
} from '../src/utils/traoDoi.js'
import { taoKenhChat } from '../src/services/kenhChat.js'

test('Gộp tin HTTP tải bù/gửi lại giữ một ID, giữ đúng thứ tự dù phản hồi đảo', () => {
  assert.deepEqual(
    gopTin([{ id: 3 }, { id: 1 }], [{ id: 2 }, { id: 3, noi_dung: 'mới' }]),
    [{ id: 1 }, { id: 2 }, { id: 3, noi_dung: 'mới' }],
  )
})
test('Reconnect tải hết 123 tin qua ba trang và nhận quyền mới dù không có tin mới', async () => {
  let ds = [],
    calls = []
  await taiTinMoi(
    async (cursor) => {
      calls.push(cursor)
      const tin_nhan = Array.from(
        { length: Math.min(50, 123 - cursor) },
        (_, i) => ({ id: cursor + i + 1 }),
      )
      return {
        tin_nhan,
        con_tin: cursor + 50 < 123,
        hoi_thoai: { co_the_gui: true },
      }
    },
    0,
    (d) => {
      ds = gopTin(ds, d.tin_nhan)
    },
  )
  assert.equal(ds.length, 123)
  assert.deepEqual(calls, [0, 50, 100])
  let hoi
  await taiTinMoi(
    async () => ({
      tin_nhan: [],
      con_tin: false,
      hoi_thoai: { co_the_gui: false },
    }),
    123,
    (d) => {
      hoi = d.hoi_thoai
    },
  )
  assert.equal(hoi.co_the_gui, false)
})
test('Kết quả phiên cũ trả null không được gộp vào lịch sử phiên mới', async () => {
  await taiTinMoi(
    async () => null,
    10,
    () => assert.fail('nhận response phiên cũ'),
  )
})
test('Retry chữ/ảnh giữ UUID, thứ tự và URI ảnh; chưa rõ kết quả chặn sửa nội dung', () => {
  let n = 0
  const q = taoTinCho(() => `uuid-${++n}`)
  const a = {
    uri: 'file:///a.png',
    ten: 'a.png',
    mime: 'image/png',
    size: 20,
    width: 10,
    height: 10,
  }
  const b = { ...a, uri: 'file:///b.png' }
  const d = q.lay('Buổi tập', [a, b])
  assert.deepEqual(q.lay('Buổi tập', [a, b]), d)
  assert.throws(() => q.lay('Buổi tập', [b, a]), /Chưa rõ/)
  assert.throws(() => q.lay('Đổi chữ', [a, b]), /Chưa rõ/)
  a.uri = 'file:///khac.png'
  assert.equal(d.anh[0].uri, 'file:///a.png')
  q.xong()
  assert.equal(q.lay('Tin khác', []).client_message_id, 'uuid-2')
})
test('Chặn rỗng, quá dài, định dạng/khối lượng/kích thước ảnh ngoài hợp đồng', () => {
  const a = {
    mime: 'image/png',
    size: 5 * 1024 * 1024,
    width: 8000,
    height: 8000,
  }
  kiemTraTin('', [a])
  assert.throws(() => kiemTraTin('  ', []))
  assert.throws(() => kiemTraTin('x'.repeat(4001), []))
  for (const v of [
    { mime: 'image/heic' },
    { size: a.size + 1 },
    { size: 0 },
    { width: 8001 },
  ])
    assert.throws(() => kiemTraTin('', [{ ...a, ...v }]))
  assert.throws(() => kiemTraTin('', Array(5).fill(a)))
})
test('Ảnh riêng dạng data URI mã hóa chính xác cả byte cuối và ảnh lớn', () => {
  for (const n of [1, 2, 3, 1025, 100000]) {
    const bytes = Uint8Array.from({ length: n }, (_, i) => i % 256)
    assert.equal(
      anhDataUri({ mime: 'image/png', bytes }),
      `data:image/png;base64,${Buffer.from(bytes).toString('base64')}`,
    )
  }
})
test('Thông báo chỉ mở đúng màn theo vai trò; từ chối URL, encoded path, ID lạ và thanh toán chưa có màn', () => {
  assert.deepEqual(dichThongBao('/khach-hang/ke-hoach/13', 'KHACH_HANG'), {
    name: 'ChiTietGiaoAn',
    params: { id: 13 },
  })
  assert.deepEqual(dichThongBao('/pt/hoc-vien/7/ke-hoach', 'HUAN_LUYEN_VIEN'), {
    name: 'GiaoAnHocVien',
    params: { khachId: 7 },
  })
  assert.deepEqual(dichThongBao('/khach-hang/ho-so', 'KHACH_HANG'), {
    name: 'CaNhan',
  })
  for (const p of [
    '/pt/ke-hoach/1',
    '/khach-hang/ke-hoach/0',
    '/khach-hang/ke-hoach/1?x=2',
    '/khach-hang/ke-hoach/9007199254740992',
    '/khach-hang/ke-hoach/%31',
    '//example.com',
    'https://example.com',
    '/khach-hang/don-mua/1',
    null,
  ])
    assert.equal(dichThongBao(p, 'KHACH_HANG'), null)
})

function boSocket(xacThuc = async () => ({ auth: 'chữ ký thử' })) {
  const sockets = [],
    timers = new Map(),
    status = []
  let i = 0,
    signals = 0
  const k = taoKenhChat({
    url: 'ws://localhost:8082',
    appKey: 'public',
    taiKhoanId: 12,
    xacThuc,
    dongBo: () => signals++,
    trangThai: (d) => status.push(d),
    hen: (fn, ms) => {
      timers.set(++i, { fn, ms })
      return i
    },
    huyHen: (id) => timers.delete(id),
    taoSocket: (url) => {
      const s = {
        url,
        readyState: 1,
        frames: [],
        send(f) {
          this.frames.push(JSON.parse(f))
        },
        close() {
          this.closed = true
        },
      }
      sockets.push(s)
      return s
    },
  })
  const nhan = async (event, data, channel) =>
    sockets.at(-1).onmessage({
      data: JSON.stringify({ event, data: JSON.stringify(data), channel }),
    })
  return { k, sockets, timers, status, nhan, signals: () => signals }
}
test('Socket auth kênh cá nhân, subscribed tải HTTP, chỉ nhận tín hiệu đúng kênh và heartbeat', async () => {
  const b = boSocket(async (id, kenh) => {
    assert.equal(id, '1.2')
    assert.equal(kenh, 'private-chat.tai-khoan.12')
    return { auth: 'a' }
  })
  b.k.ketNoi()
  await b.nhan('pusher:connection_established', {
    socket_id: '1.2',
    activity_timeout: 30,
  })
  assert.deepEqual(b.sockets[0].frames[0], {
    event: 'pusher:subscribe',
    data: { channel: 'private-chat.tai-khoan.12', auth: 'a' },
  })
  await b.nhan(
    'pusher_internal:subscription_succeeded',
    {},
    'private-chat.tai-khoan.12',
  )
  await b.nhan(
    'chat.cap-nhat',
    { can_dong_bo: true },
    'private-chat.tai-khoan.13',
  )
  assert.equal(b.signals(), 1)
  await b.nhan(
    'chat.cap-nhat',
    { can_dong_bo: true },
    'private-chat.tai-khoan.12',
  )
  assert.equal(b.signals(), 2)
  await b.nhan('pusher:ping', {})
  assert.equal(b.sockets[0].frames.at(-1).event, 'pusher:pong')
  const idle = [...b.timers.values()].find((t) => t.ms === 30000)
  idle.fn()
  assert.equal(b.sockets[0].frames.at(-1).event, 'pusher:ping')
  b.k.dong()
  assert.equal(b.timers.size, 0)
  assert.equal(b.sockets[0].closed, true)
})
test('Đăng xuất giữa auth hủy request, bỏ auth muộn, không gửi subscribe bằng phiên cũ', async () => {
  let resolve, signal
  const b = boSocket((id, kenh, s) => {
    signal = s
    return new Promise((r) => {
      resolve = r
    })
  })
  b.k.ketNoi()
  const pending = b.nhan('pusher:connection_established', { socket_id: '1.2' })
  b.k.dong()
  assert.equal(signal.aborted, true)
  resolve({ auth: 'cũ' })
  await pending
  assert.equal(b.sockets[0].frames.length, 0)
  assert.equal(b.timers.size, 0)
})
test('Mất socket lên lịch reconnect và foreground kết nối lại; auth 403 dừng socket nhưng HTTP vẫn có thể đồng bộ', async () => {
  const b = boSocket()
  b.k.ketNoi()
  b.sockets[0].onclose({ code: 1006 })
  assert.equal([...b.timers.values()][0].ms, 1000)
  ;[...b.timers.values()][0].fn()
  assert.equal(b.sockets.length, 2)
  b.k.dong()
  b.k.ketNoi()
  assert.equal(b.sockets.length, 3)
  b.k.dong()
  const c = boSocket(async () => {
    throw Object.assign(new Error('không có quyền'), { status: 403 })
  })
  c.k.ketNoi()
  await c.nhan('pusher:connection_established', { socket_id: '1.2' })
  assert.equal(c.timers.size, 0)
  assert.equal(c.sockets[0].closed, true)
  c.k.dong()
})
