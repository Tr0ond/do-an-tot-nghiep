import test from 'node:test'
import assert from 'node:assert/strict'
import { taoQuanLyPhien } from '../src/services/quanLyPhien.js'

const nguoi = (id) => ({
  id,
  ho_ten: `QA ${id}`,
  vai_tro: 'KHACH_HANG',
  trang_thai: 'HOAT_DONG',
})
const phien = (id) => ({
  access_token: `qa-${id}`,
  expires_at: new Date(Date.now() + 86400000).toISOString(),
  tai_khoan: nguoi(id),
})
const cho = () => {
  let xong
  const promise = new Promise((resolve) => {
    xong = resolve
  })
  return { promise, xong }
}
test('dịch vụ lịch giữ metadata và không ghi đè hồ sơ; 403 tài nguyên giữ phiên', async () => {
  const b = bo()
  await b.manager.dangNhap({})
  const envelope = { data: [{ id: 12 }], meta: { last_page: 2 } }
  assert.deepEqual(await b.manager.goiDichVu(async () => envelope), envelope)
  assert.equal(b.state.at(-1).taiKhoan.id, 1)
  await assert.rejects(
    b.manager.goiDichVu(async () => {
      throw Object.assign(new Error('Không có quyền'), { status: 403 })
    }),
  )
  assert.ok(b.doc())
})
test('dịch vụ lịch cũ sau đổi tài khoản bị bỏ; 401 đóng phiên hiện tại', async () => {
  const b = bo()
  await b.manager.dangNhap({})
  const deferred = cho()
  const cu = b.manager.goiDichVu(() => deferred.promise)
  await b.manager.dangXuat()
  b.api.dangNhap = async () => phien(2)
  await b.manager.dangNhap({})
  deferred.xong({ data: [{ id: 12 }] })
  assert.equal(await cu, null)
  await assert.rejects(
    b.manager.goiDichVu(async () => {
      throw Object.assign(new Error('Hết hạn'), { status: 401 })
    }),
  )
  assert.equal(b.doc(), null)
  assert.equal(b.state.at(-1).taiKhoan, null)
})
function bo(overrides = {}) {
  const state = []
  let luu = null
  const kho = {
    doc: async () => luu,
    luu: async (data) => {
      luu = JSON.stringify(data)
    },
    xoa: async () => {
      luu = null
    },
    ...overrides.kho,
  }
  const api = {
    dangNhap: async () => phien(1),
    taiHoSo: async () => nguoi(1),
    luuHoSo: async () => nguoi(1),
    dangXuat: async () => {},
    huyYeuCau() {},
    ...overrides.api,
  }
  const manager = taoQuanLyPhien({
    kho,
    api,
    onChange: (data) => state.push(data),
  })
  return { manager, state, kho, api, doc: () => luu }
}
test('chỉ lưu token/hạn, không lưu mật khẩu hay hồ sơ; logout xóa kho', async () => {
  const b = bo()
  await b.manager.dangNhap({ password: 'khong-luu' })
  assert.deepEqual(Object.keys(JSON.parse(b.doc())).sort(), [
    'access_token',
    'expires_at',
  ])
  assert.equal(b.state.at(-1).taiKhoan.id, 1)
  await b.manager.dangXuat()
  assert.equal(b.doc(), null)
  assert.equal(b.state.at(-1).taiKhoan, null)
})
test('mạng lỗi khi khôi phục giữ token nhưng không mở dữ liệu riêng; thử lại thành công', async () => {
  let loi = true
  const b = bo({
    api: {
      taiHoSo: async () => {
        if (loi) throw new Error('Mất mạng')
        return nguoi(1)
      },
    },
  })
  await b.kho.luu(phien(1))
  await b.manager.khoiPhuc()
  assert.equal(b.state.at(-1).taiKhoan, null)
  assert.ok(b.doc())
  assert.equal(
    b.state.find((x) => x.loiKhoiPhuc === 'Mất mạng')?.loiKhoiPhuc,
    'Mất mạng',
  )
  loi = false
  await b.manager.khoiPhuc()
  assert.equal(b.state.at(-1).taiKhoan.id, 1)
  assert.equal(
    b.state.filter((x) => x.dangKhoiPhuc === true).at(-1).loiKhoiPhuc,
    '',
  )
})
test('401 khôi phục xóa token; token hết hạn không gọi me', async () => {
  let goi = 0
  const b = bo({
    api: {
      taiHoSo: async () => {
        goi++
        throw Object.assign(new Error('Hết hạn'), { status: 401 })
      },
    },
  })
  await b.kho.luu(phien(1))
  await b.manager.khoiPhuc()
  assert.equal(b.doc(), null)
  await b.kho.luu({ ...phien(1), expires_at: '2020-01-01T00:00:00Z' })
  await b.manager.khoiPhuc()
  assert.equal(goi, 1)
  assert.equal(b.doc(), null)
})
test('response hồ sơ cũ về sau logout không ghi đè người mới', async () => {
  const deferred = cho()
  const b = bo({ api: { taiHoSo: () => deferred.promise } })
  await b.manager.dangNhap({})
  const cu = b.manager.taiHoSo()
  await b.manager.dangXuat()
  b.api.dangNhap = async () => phien(2)
  await b.manager.dangNhap({})
  deferred.xong(nguoi(1))
  assert.equal(await cu, null)
  assert.equal(b.state.at(-1).taiKhoan.id, 2)
})
test('logout khi ghi SecureStore đang chờ: xóa sau ghi, thu hồi token đến muộn', async () => {
  const deferred = cho()
  let daGhi = false
  let daXoa = false
  const thuHoi = []
  const b = bo({
    kho: {
      luu: async () => {
        await deferred.promise
        daGhi = true
      },
      xoa: async () => {
        assert.equal(daGhi, true)
        daXoa = true
      },
    },
    api: { dangXuat: async (token) => thuHoi.push(token) },
  })
  const login = b.manager.dangNhap({})
  await new Promise((resolve) => setImmediate(resolve))
  const logout = b.manager.dangXuat()
  deferred.xong()
  await Promise.all([login, logout])
  assert.equal(daXoa, true)
  assert.ok(thuHoi.includes('qa-1'))
  assert.equal(b.state.at(-1).taiKhoan, null)
})
test('lưu kho lỗi không mở tài khoản và thu hồi token mới', async () => {
  let daThuHoi = false
  const b = bo({
    kho: {
      luu: async () => {
        throw new Error('Kho lỗi')
      },
    },
    api: {
      dangXuat: async () => {
        daThuHoi = true
      },
    },
  })
  await assert.rejects(b.manager.dangNhap({}), /Kho lỗi/)
  assert.equal(daThuHoi, true)
  assert.equal(b.state.at(-1).taiKhoan, null)
})
test('double-submit cấp một phiên; Admin không vào app', async () => {
  const deferred = cho()
  let dem = 0
  const b = bo({
    api: {
      dangNhap: async () => {
        dem++
        return deferred.promise
      },
    },
  })
  const a = b.manager.dangNhap({})
  await b.manager.dangNhap({})
  deferred.xong({ ...phien(1), tai_khoan: { ...nguoi(1), vai_tro: 'ADMIN' } })
  await assert.rejects(a, /không được phép/)
  assert.equal(dem, 1)
  assert.equal(b.state.at(-1).taiKhoan, null)
})
test('401 trong khi lưu đóng phiên; lỗi 409 giữ hồ sơ trước và phiên', async () => {
  const b = bo({
    api: {
      luuHoSo: async () => {
        throw Object.assign(new Error('Bản cũ'), { status: 409 })
      },
    },
  })
  await b.manager.dangNhap({})
  await assert.rejects(b.manager.luuHoSo({}), /Bản cũ/)
  assert.equal(b.state.at(-1).taiKhoan.id, 1)
  b.api.luuHoSo = async () => {
    throw Object.assign(new Error('Phiên hết hạn'), { status: 401 })
  }
  await assert.rejects(b.manager.luuHoSo({}), /Phiên hết hạn/)
  assert.equal(b.state.at(-1).taiKhoan, null)
  assert.equal(b.doc(), null)
})
test('logout mất mạng báo chưa thu hồi, không giữ tài khoản trên máy', async () => {
  const b = bo({
    api: {
      dangXuat: async () => {
        throw new Error('Mất mạng')
      },
    },
  })
  await b.manager.dangNhap({})
  await b.manager.dangXuat()
  assert.equal(b.doc(), null)
  assert.equal(b.state.at(-1).taiKhoan, null)
  assert.match(b.state.at(-1).loiPhien, /chưa xác nhận thu hồi/)
})
