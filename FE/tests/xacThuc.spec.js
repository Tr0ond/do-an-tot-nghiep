import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useXacThucStore } from '../src/stores/xacThuc'
import xacThucService from '../src/services/xacThucService'

vi.mock('../src/services/xacThucService', () => ({
  default: { taiTaiKhoan: vi.fn(), dangNhap: vi.fn(), dangKy: vi.fn(), dangXuat: vi.fn() },
}))

describe('Session tài khoản', () => {
  let xacThuc
  const taiKhoan = { id: 1, ho_ten: 'Khách thử', vai_tro: 'KHACH_HANG' }
  beforeEach(() => {
    vi.resetAllMocks()
    setActivePinia(createPinia())
    xacThuc = useXacThucStore()
    xacThuc.taiKhoan = { ...taiKhoan }
  })

  it('lấy lại tài khoản từ server khi khởi động', async () => {
    xacThucService.taiTaiKhoan.mockResolvedValue({ data: { id: 2, vai_tro: 'ADMIN' } })
    await xacThuc.taiTaiKhoan()
    expect(xacThuc.taiKhoan.id).toBe(2)
    expect(xacThuc.daKhoiTao).toBe(true)
  })

  it.each([401, 403])('xóa tài khoản cũ khi /me bị từ chối %s', async (status) => {
    xacThucService.taiTaiKhoan.mockRejectedValue({ response: { status } })
    await xacThuc.taiTaiKhoan()
    expect(xacThuc.daDangNhap).toBe(false)
  })

  it('đăng xuất thành công xóa dữ liệu tài khoản', async () => {
    xacThucService.dangXuat.mockResolvedValue({ status: true })
    await xacThuc.dangXuat()
    expect(xacThuc.taiKhoan).toBeNull()
  })

  it('session đã mất ở server cũng cho kết thúc đăng xuất', async () => {
    xacThucService.dangXuat.mockRejectedValue({ response: { status: 401 } })
    await xacThuc.dangXuat()
    expect(xacThuc.taiKhoan).toBeNull()
  })

  it('CSRF 419 không được coi là đã đăng xuất', async () => {
    const loi = { response: { status: 419 } }
    xacThucService.dangXuat.mockRejectedValue(loi)
    await expect(xacThuc.dangXuat()).rejects.toEqual(loi)
    expect(xacThuc.taiKhoan).toEqual(taiKhoan)
  })

  it('mất mạng khi đăng xuất giữ trạng thái để báo lỗi và thử lại', async () => {
    const loi = new Error('Mất mạng')
    xacThucService.dangXuat.mockRejectedValue(loi)
    await expect(xacThuc.dangXuat()).rejects.toThrow('Mất mạng')
    expect(xacThuc.taiKhoan).toEqual(taiKhoan)
  })
})
