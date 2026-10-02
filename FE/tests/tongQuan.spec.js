import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useXacThucStore } from '../src/stores/xacThuc'
import { kiemTraDieuHuong } from '../src/router/kiemTraDieuHuong'
import TongQuan from '../src/views/TongQuan/index.vue'
import xacThucService from '../src/services/xacThucService'
import tongQuanService from '../src/services/tongQuanService'

vi.mock('../src/services/xacThucService', () => ({ default: { taiTaiKhoan: vi.fn() } }))
vi.mock('../src/services/tongQuanService', () => ({ default: { taiTongQuan: vi.fn() } }))

function taoTrang() {
  const trang = { ...TongQuan.data() }
  for (const [ten, ham] of Object.entries(TongQuan.methods)) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(TongQuan.computed))
    Object.defineProperty(trang, ten, { get: () => ham.call(trang) })
  return trang
}
function choKetQua() {
  let resolve
  const promise = new Promise((traKetQua) => {
    resolve = traKetQua
  })
  return { promise, resolve }
}
const duLieuKhach = {
  vai_tro: 'KHACH_HANG',
  cap_nhat_luc: '2026-10-02T03:00:00Z',
  thu_vien: { bai_tap: 0, nhom_co: 0, goi_tap: 0 },
  ho_so: { hoan_thanh: 1, tong_muc: 6, cac_muc: [] },
}

describe('Trang chủ và dashboard', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    setActivePinia(createPinia())
  })
  it.each([
    ['KHACH_HANG', '/khach-hang/tong-quan'],
    ['ADMIN', '/admin/tong-quan'],
    ['HUAN_LUYEN_VIEN', '/pt/tong-quan'],
  ])('mở trang chủ trực tiếp sau khi khôi phục session %s', async (vai_tro, path) => {
    const store = useXacThucStore()
    xacThucService.taiTaiKhoan.mockResolvedValue({ data: { id: 2, vai_tro } })
    expect(await kiemTraDieuHuong({ path: '/', hash: '#tinh-nang', meta: {} }, store)).toEqual({
      path,
      replace: true,
    })
    expect(store.duongDanCaNhan).toBe(path)
  })
  it('không chọn trang giới thiệu trước khi session trả về', async () => {
    const cho = choKetQua()
    xacThucService.taiTaiKhoan.mockReturnValue(cho.promise)
    let daDieuHuong = false
    const ketQua = kiemTraDieuHuong({ path: '/', meta: {} }, useXacThucStore()).then((r) => {
      daDieuHuong = true
      return r
    })
    await Promise.resolve()
    expect(daDieuHuong).toBe(false)
    cho.resolve({ data: { id: 2, vai_tro: 'ADMIN' } })
    expect(await ketQua).toEqual({ path: '/admin/tong-quan', replace: true })
  })
  it('session hết hạn đưa trang chủ về giới thiệu và trang riêng về đăng nhập', async () => {
    const store = useXacThucStore()
    store.taiKhoan = { vai_tro: 'ADMIN' }
    xacThucService.taiTaiKhoan.mockRejectedValue({ response: { status: 401 } })
    expect(await kiemTraDieuHuong({ path: '/', meta: {} }, store)).toBeUndefined()
    expect(store.daDangNhap).toBe(false)
    expect(
      await kiemTraDieuHuong({ path: '/admin/tong-quan', meta: { vaiTro: 'ADMIN' } }, store),
    ).toBe('/dang-nhap')
  })
  it('khách không vào được dashboard Admin bằng URL', async () => {
    xacThucService.taiTaiKhoan.mockResolvedValue({ data: { vai_tro: 'KHACH_HANG' } })
    expect(
      await kiemTraDieuHuong(
        { path: '/admin/tong-quan', meta: { vaiTro: 'ADMIN' } },
        useXacThucStore(),
      ),
    ).toBe('/khong-co-quyen')
  })
  it('mất kết nối khi xác định trang chủ không giả coi session là guest', async () => {
    xacThucService.taiTaiKhoan.mockRejectedValue(new Error('Mạng'))
    expect(await kiemTraDieuHuong({ path: '/', meta: {} }, useXacThucStore())).toBe(
      '/khong-ket-noi',
    )
  })
  it('người đã đăng nhập mở đăng nhập/đăng ký được đưa về dashboard', async () => {
    xacThucService.taiTaiKhoan.mockResolvedValue({ data: { vai_tro: 'KHACH_HANG' } })
    expect(
      await kiemTraDieuHuong({ path: '/dang-ky', meta: { khach: true } }, useXacThucStore()),
    ).toEqual({ path: '/khach-hang/tong-quan', replace: true })
  })
  it('thư viện rỗng vẫn hiển thị 0 và phần trăm đúng, không thêm số liệu giả', async () => {
    useXacThucStore().taiKhoan = { vai_tro: 'KHACH_HANG', ho_ten: 'Khách' }
    tongQuanService.taiTongQuan.mockResolvedValue({ data: duLieuKhach })
    const trang = taoTrang()
    await trang.taiTongQuan()
    expect(trang.cacThongKe.map((muc) => muc.so)).toEqual([17, 0, 0, 0])
    expect(trang.cacThongKe[0].nhan).toBe('Hồ sơ hoàn thiện')
    expect(trang.cacLoiTat.every((muc) => !muc.to.startsWith('/admin'))).toBe(true)
    expect(trang.dangTai).toBe(false)
  })
  it('cập nhật liên tiếp hủy request trước và bỏ qua kết quả đến muộn', async () => {
    useXacThucStore().taiKhoan = { vai_tro: 'KHACH_HANG' }
    const cho = choKetQua()
    tongQuanService.taiTongQuan
      .mockReturnValueOnce(cho.promise)
      .mockResolvedValueOnce({ data: { ...duLieuKhach, thu_vien: { bai_tap: 22 } } })
    const trang = taoTrang()
    const cu = trang.taiTongQuan()
    const signal = trang.boHuy.signal
    await trang.taiTongQuan()
    expect(signal.aborted).toBe(true)
    cho.resolve({ data: duLieuKhach })
    await cu
    expect(trang.duLieu.thu_vien.bai_tap).toBe(22)
  })
  it('rời trang hủy request và không ghi dữ liệu sau unmount', async () => {
    useXacThucStore().taiKhoan = { vai_tro: 'KHACH_HANG' }
    const cho = choKetQua()
    tongQuanService.taiTongQuan.mockReturnValue(cho.promise)
    const trang = taoTrang()
    const pending = trang.taiTongQuan()
    TongQuan.beforeUnmount.call(trang)
    expect(trang.boHuy.signal.aborted).toBe(true)
    cho.resolve({ data: duLieuKhach })
    await pending
    expect(trang.duLieu).toBeNull()
  })
  it('401 xóa dashboard và tài khoản cũ, lỗi mạng cho phép thử lại', async () => {
    const store = useXacThucStore()
    store.taiKhoan = { vai_tro: 'KHACH_HANG' }
    const trang = taoTrang()
    trang.duLieu = duLieuKhach
    tongQuanService.taiTongQuan.mockRejectedValue({
      response: { status: 401, data: { message: 'Phiên đã hết hạn.' } },
    })
    await trang.taiTongQuan()
    expect(trang.duLieu).toBeNull()
    expect(store.taiKhoan).toBeNull()
    expect(trang.hetPhien).toBe(true)
    store.taiKhoan = { vai_tro: 'KHACH_HANG' }
    tongQuanService.taiTongQuan.mockRejectedValue(new Error('Mạng'))
    await trang.taiTongQuan()
    expect(trang.hetPhien).toBe(false)
    expect(trang.thongBao).toContain('Không thể kết nối')
    tongQuanService.taiTongQuan.mockResolvedValue({ data: duLieuKhach })
    await trang.taiTongQuan()
    expect(trang.thongBao).toBe('')
    expect(trang.duLieu).toEqual(duLieuKhach)
  })
})
