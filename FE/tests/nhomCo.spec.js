import { beforeEach, describe, expect, it, vi } from 'vitest'
import BieuMau from '../src/views/Admin/NhomCo/BieuMau/index.vue'
import DanhSach from '../src/views/Admin/NhomCo/index.vue'
import nhomCoService from '../src/services/nhomCoService'

vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/services/nhomCoService', () => ({
  default: {
    taiDanhSach: vi.fn(),
    taiChiTiet: vi.fn(),
    taoNhomCo: vi.fn(),
    suaNhomCo: vi.fn(),
    datTrangThai: vi.fn(),
  },
}))

function choKetQua() {
  let resolve
  const promise = new Promise((thanhCong) => {
    resolve = thanhCong
  })
  return { promise, resolve }
}

function taoTrang(component, ghiDe = {}) {
  const trang = {
    ...component.data(),
    $route: { path: '/admin/nhom-co/them', params: {}, query: {} },
    $router: { replace: vi.fn(), push: vi.fn() },
    $refs: { xacNhan: { close: vi.fn(), showModal: vi.fn() } },
    $nextTick: async () => {},
    ...ghiDe,
  }
  for (const [ten, ham] of Object.entries(component.methods)) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(component.computed ?? {}))
    Object.defineProperty(trang, ten, { get: () => ham.call(trang) })
  return trang
}

const nhom = {
  id: 7,
  ma_nhom_co: 'chest',
  ten_nhom_co: 'Ngực',
  ten_nguon: 'chest',
  trang_thai: 'HOAT_DONG',
  so_bai_tap: 8,
  so_bai_hoat_dong: 6,
  so_bai_hien_thi: 6,
  updated_at: '2026-10-01 10:00:00.000001',
}
function trangSua(ghiDe = {}) {
  const trang = taoTrang(BieuMau, {
    $route: { path: '/admin/nhom-co/7/sua', params: { id: '7' } },
    ...ghiDe,
  })
  trang.napDuLieu(nhom)
  return trang
}

describe('Quản lý nhóm cơ', () => {
  beforeEach(() => vi.resetAllMocks())
  it('giữ thông báo tạo thành công sau khi chuyển sang trang sửa', async () => {
    const trang = taoTrang(BieuMau)
    trang.bieuMau = { ma_nhom_co: 'chest', ten_nhom_co: 'Ngực' }
    nhomCoService.taoNhomCo.mockResolvedValue({ data: nhom, message: 'Đã thêm nhóm cơ.' })
    trang.$router.replace.mockImplementation(async () => {
      trang.$route = { path: '/admin/nhom-co/7/sua', params: { id: '7' } }
      trang.thongBao = ''
      trang.lanTai++
      trang.dangLuu = false
    })
    await trang.luuNhomCo()
    expect(trang.thongBao).toBe('Đã thêm nhóm cơ.')
    expect(trang.coLoi).toBe(false)
    expect(trang.coThayDoi).toBe(false)
  })
  it('tạo chỉ gửi mã/tên, chuẩn hóa mã, chặn gửi trùng và giữ dữ liệu khi mất mạng', async () => {
    const cho = choKetQua()
    nhomCoService.taoNhomCo
      .mockReturnValueOnce(cho.promise)
      .mockRejectedValueOnce(new Error('Mất mạng'))
    const trang = taoTrang(BieuMau)
    trang.bieuMau = {
      ma_nhom_co: ' CORE_01 ',
      ten_nhom_co: ' Cơ trung tâm ',
      trang_thai: 'NGUNG_SU_DUNG',
      ten_nguon: 'Giả',
    }
    const lanDau = trang.luuNhomCo()
    await trang.luuNhomCo()
    expect(nhomCoService.taoNhomCo).toHaveBeenCalledTimes(1)
    expect(nhomCoService.taoNhomCo.mock.calls[0][0]).toEqual({
      ma_nhom_co: 'core_01',
      ten_nhom_co: 'Cơ trung tâm',
    })
    cho.resolve({ data: nhom, message: 'Đã thêm' })
    await lanDau
    expect(trang.$router.replace).toHaveBeenCalledWith('/admin/nhom-co/7/sua')
    const mang = taoTrang(BieuMau)
    mang.bieuMau = { ma_nhom_co: 'core_01', ten_nhom_co: 'Cơ trung tâm' }
    await mang.luuNhomCo()
    expect(mang.bieuMau.ten_nhom_co).toBe('Cơ trung tâm')
    expect(mang.dangLuu).toBe(false)
    expect(mang.coLoi).toBe(true)
  })
  it('sửa copy dữ liệu, không gửi mã/nguồn/trạng thái và giữ lỗi 422 cạnh trường', async () => {
    const trang = trangSua()
    trang.bieuMau.ten_nhom_co = 'Tên khác'
    expect(nhom.ten_nhom_co).toBe('Ngực')
    nhomCoService.suaNhomCo.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Tên không hợp lệ', errors: { ten_nhom_co: ['Quá dài'] } },
      },
    })
    await trang.luuNhomCo()
    expect(nhomCoService.suaNhomCo).toHaveBeenCalledWith('7', {
      ten_nhom_co: 'Tên khác',
      updated_at: nhom.updated_at,
    })
    expect(trang.bieuMau.ten_nhom_co).toBe('Tên khác')
    expect(trang.loiTruong.ten_nhom_co).toEqual(['Quá dài'])
    expect(trang.coThayDoi).toBe(true)
  })
  it('409 giữ form, khóa ghi tiếp và tải lại phiên bản mới', async () => {
    const trang = trangSua()
    trang.bieuMau.ten_nhom_co = 'Nội dung đang soạn'
    nhomCoService.suaNhomCo.mockRejectedValue({
      response: { status: 409, data: { message: 'Bản cũ' } },
    })
    await trang.luuNhomCo()
    await trang.luuNhomCo()
    expect(nhomCoService.suaNhomCo).toHaveBeenCalledTimes(1)
    expect(trang.xungDot).toBe(true)
    expect(trang.bieuMau.ten_nhom_co).toBe('Nội dung đang soạn')
    nhomCoService.taiChiTiet.mockResolvedValue({
      data: { ...nhom, ten_nhom_co: 'Bản mới', updated_at: '2026-10-01 10:00:00.000002' },
    })
    await trang.taiChiTiet()
    expect(trang.phienBan).toBe('2026-10-01 10:00:00.000002')
    expect(trang.xungDot).toBe(false)
    expect(trang.coThayDoi).toBe(false)
  })
  it('gửi phiên bản null cho record cũ và hiển thị lỗi phiên hết hạn', async () => {
    const trang = trangSua()
    trang.napDuLieu({ ...nhom, updated_at: null })
    trang.bieuMau.ten_nhom_co = 'Đổi tên'
    nhomCoService.suaNhomCo.mockRejectedValue({
      response: { status: 419, data: { message: 'Phiên hết hạn' } },
    })
    await trang.luuNhomCo()
    expect(nhomCoService.suaNhomCo.mock.calls[0][1].updated_at).toBeNull()
    expect(trang.hetPhien).toBe(true)
    expect(trang.bieuMau.ten_nhom_co).toBe('Đổi tên')
  })
  it('bỏ phản hồi ghi đến muộn sau khi chuyển nhóm', async () => {
    const cho = choKetQua()
    nhomCoService.suaNhomCo.mockReturnValue(cho.promise)
    const trang = trangSua()
    trang.bieuMau.ten_nhom_co = 'Tên đang gửi'
    const dangGhi = trang.luuNhomCo()
    trang.$route = { path: '/admin/nhom-co/9/sua', params: { id: '9' } }
    trang.lanTai++
    trang.napDuLieu({ ...nhom, id: 9, ten_nhom_co: 'Nhóm mới' })
    cho.resolve({ data: { ...nhom, ten_nhom_co: 'Tên đang gửi' }, message: 'Đã lưu' })
    await dangGhi
    expect(trang.bieuMau.ten_nhom_co).toBe('Nhóm mới')
    expect(trang.thongBao).toBe('')
  })
  it('hủy request list cũ và không nhận phản hồi sau unmount', async () => {
    const mot = choKetQua()
    const hai = choKetQua()
    nhomCoService.taiDanhSach.mockReturnValueOnce(mot.promise).mockReturnValueOnce(hai.promise)
    const trang = taoTrang(DanhSach)
    const lanDau = trang.taiDanhSach()
    const signal = nhomCoService.taiDanhSach.mock.calls[0][1]
    const lanHai = trang.taiDanhSach()
    expect(signal.aborted).toBe(true)
    hai.resolve({ data: [nhom], meta: { total: 1 } })
    await lanHai
    mot.resolve({ data: [], meta: { total: 0 } })
    await lanDau
    expect(trang.danhSach).toEqual([nhom])
    const muon = choKetQua()
    nhomCoService.taiDanhSach.mockReturnValue(muon.promise)
    const lanCuoi = trang.taiDanhSach()
    DanhSach.beforeUnmount.call(trang)
    muon.resolve({ data: [], meta: { total: 0 } })
    await lanCuoi
    expect(trang.danhSach).toEqual([nhom])
  })
  it('xác nhận đổi trạng thái dùng phiên bản đang xem, chặn double submit và tải lại khi 409', async () => {
    const cho = choKetQua()
    const trang = taoTrang(DanhSach)
    trang.taiDanhSach = vi.fn()
    await trang.moXacNhan(nhom)
    expect(trang.$refs.xacNhan.showModal).toHaveBeenCalledOnce()
    nhomCoService.datTrangThai
      .mockReturnValueOnce(cho.promise)
      .mockRejectedValueOnce({ response: { status: 409, data: { message: 'Nhóm đã đổi' } } })
    const lanDau = trang.doiTrangThai()
    await trang.doiTrangThai()
    expect(nhomCoService.datTrangThai).toHaveBeenCalledTimes(1)
    expect(nhomCoService.datTrangThai).toHaveBeenCalledWith(7, {
      trang_thai: 'NGUNG_SU_DUNG',
      updated_at: nhom.updated_at,
    })
    const suKien = { preventDefault: vi.fn() }
    trang.huyXacNhan(suKien)
    expect(suKien.preventDefault).toHaveBeenCalledOnce()
    cho.resolve({ message: 'Đã ngừng' })
    await lanDau
    expect(trang.$refs.xacNhan.close).toHaveBeenCalledOnce()
    await trang.doiTrangThai()
    expect(trang.coLoi).toBe(true)
    expect(trang.taiDanhSach).toHaveBeenCalledTimes(2)
    expect(trang.dangDoi).toBe(false)
  })
})
