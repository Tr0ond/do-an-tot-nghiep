import { beforeEach, expect, it, vi } from 'vitest'
import Page from '../src/views/Admin/TaiLieuTuVan/index.vue'
import service from '../src/services/taiLieuTuVanService'
vi.mock('../src/services/taiLieuTuVanService', () => ({
  default: { taiDanhSach: vi.fn(), thongKe: vi.fn(), tao: vi.fn(), sua: vi.fn(), thaoTac: vi.fn() },
}))
vi.mock('../src/services/xacThucService', () => ({ default: {} }))
function trang(them = {}) {
  const p = { ...Page.data(), ...them }
  for (const [k, f] of Object.entries(Page.methods)) p[k] = f.bind(p)
  return p
}
beforeEach(() => {
  vi.resetAllMocks()
  service.taiDanhSach.mockResolvedValue({ data: [], meta: { last_page: 1 } })
  service.thongKe.mockResolvedValue({ data: {} })
})
it('tạo tài liệu mất mạng giữ UUID, thay nội dung dùng UUID mới', async () => {
  const p = trang()
  p.soanMoi()
  p.banNhap.tieu_de = 'FAQ'
  p.banNhap.noi_dung = 'Nội dung'
  service.tao.mockRejectedValue(new Error('Mất mạng'))
  await p.luu()
  await p.luu()
  expect(service.tao.mock.calls[0][0].client_request_id).toBe(
    service.tao.mock.calls[1][0].client_request_id,
  )
  p.banNhap.noi_dung = 'Nội dung khác'
  await p.luu()
  expect(service.tao.mock.calls[2][0].client_request_id).not.toBe(
    service.tao.mock.calls[0][0].client_request_id,
  )
})
it('sửa version cũ giữ bản đang soạn khi xung đột', async () => {
  const p = trang()
  p.sua({ id: 4, phien_ban: 2, tieu_de: 'FAQ', loai: 'FAQ', noi_dung: 'Nội dung' })
  p.banNhap.noi_dung = 'Bản đang soạn'
  service.sua.mockRejectedValue({ response: { data: { message: 'Tài liệu đã thay đổi.' } } })
  await p.luu()
  expect(service.sua).toHaveBeenCalledWith(4, {
    tieu_de: 'FAQ',
    loai: 'FAQ',
    noi_dung: 'Bản đang soạn',
    phien_ban: 2,
  })
  expect(p.banNhap.noi_dung).toBe('Bản đang soạn')
})
it('xuất bản cần xác nhận trong trang; hủy không gọi API', async () => {
  const p = trang()
  p.deNghi({ id: 4, phien_ban: 2, trang_thai: 'NHAP' })
  expect(service.thaoTac).not.toHaveBeenCalled()
  p.xacNhan = null
  await p.thucHien()
  expect(service.thaoTac).not.toHaveBeenCalled()
  p.deNghi({ id: 4, phien_ban: 2, trang_thai: 'NHAP' })
  service.thaoTac.mockResolvedValue({})
  await p.thucHien()
  expect(service.thaoTac).toHaveBeenCalledWith(4, 'xuat-ban', 2)
  expect(p.xacNhan).toBeNull()
})
