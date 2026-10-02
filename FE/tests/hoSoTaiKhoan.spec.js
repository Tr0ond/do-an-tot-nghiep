import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useXacThucStore } from '../src/stores/xacThuc'
import SuaHoSo from '../src/views/CaNhan/Sua/index.vue'
import KhoiPhuc from '../src/views/KhoiPhucMatKhau/index.vue'
import TaiKhoan from '../src/views/Admin/TaiKhoan/index.vue'
import xacThucService from '../src/services/xacThucService'
import taiKhoanService from '../src/services/taiKhoanService'

vi.mock('../src/services/xacThucService', () => ({
  default: { suaHoSo: vi.fn(), taiTaiKhoan: vi.fn(), guiLienKet: vi.fn(), datLaiMatKhau: vi.fn() },
}))
vi.mock('../src/services/taiKhoanService', () => ({
  default: { taiDanhSach: vi.fn(), datTrangThai: vi.fn(), taoTaiKhoan: vi.fn() },
}))

function taoTrang(component, ghiDe = {}) {
  const trang = {
    ...component.data(),
    $route: { path: '/khach-hang/ho-so/sua', hash: '' },
    $router: { replace: vi.fn() },
    $refs: { xacNhan: { showModal: vi.fn(), close: vi.fn() } },
    $nextTick: async () => {},
    ...ghiDe,
  }
  for (const [ten, ham] of Object.entries(component.methods ?? {})) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(component.computed ?? {}))
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
const khach = {
  id: 2,
  ho_ten: 'Khách demo',
  email: 'khach@example.test',
  vai_tro: 'KHACH_HANG',
  trang_thai: 'HOAT_DONG',
  updated_at: '2026-10-02 00:00:00.000001',
  ho_so_khach_hang: {
    muc_tieu: 'Tăng cơ',
    kinh_nghiem: '',
    ngay_sinh: '2000-01-01',
    gioi_tinh: '',
    thoi_gian_co_the_tap: ['Thứ hai 18:00'],
  },
}
function trangHoSo() {
  const trang = taoTrang(SuaHoSo)
  trang.napHoSo(useXacThucStore().taiKhoan)
  return trang
}
const token = 'a'.repeat(64)
function trangReset() {
  const trang = taoTrang(KhoiPhuc, {
    $route: { path: '/dat-lai-mat-khau', hash: '#token=' + token + '&email=khach%40example.test' },
  })
  trang.khoiTao()
  return trang
}

describe('Hồ sơ, khôi phục mật khẩu và khóa tài khoản', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    setActivePinia(createPinia())
    useXacThucStore().taiKhoan = structuredClone(khach)
  })
  it('copy hồ sơ, chỉ gửi trường đúng vai trò và chuẩn hóa thời gian tập', () => {
    const trang = trangHoSo()
    trang.bieuMau.ho_ten = ' Tên mới '
    trang.bieuMau.vai_tro = 'ADMIN'
    trang.thoiGianNhap = ' Thứ hai 18:00 \n\nThứ tư 19:00'
    expect(useXacThucStore().taiKhoan.ho_ten).toBe(khach.ho_ten)
    expect(trang.duLieuGui()).toEqual({
      ho_ten: 'Tên mới',
      updated_at: khach.updated_at,
      muc_tieu: 'Tăng cơ',
      kinh_nghiem: null,
      ngay_sinh: '2000-01-01',
      gioi_tinh: null,
      thoi_gian_co_the_tap: ['Thứ hai 18:00', 'Thứ tư 19:00'],
    })
    useXacThucStore().taiKhoan = {
      ...khach,
      vai_tro: 'HUAN_LUYEN_VIEN',
      ho_so_huan_luyen_vien: { chuyen_mon: 'Sức bền', gioi_thieu: 'PT' },
    }
    const pt = trangHoSo()
    expect(pt.duLieuGui()).toEqual({
      ho_ten: khach.ho_ten,
      updated_at: khach.updated_at,
      chuyen_mon: 'Sức bền',
      gioi_thieu: 'PT',
    })
  })
  it('chặn double-submit và cập nhật store sau khi server lưu hồ sơ', async () => {
    const trang = trangHoSo()
    const cho = choKetQua()
    xacThucService.suaHoSo.mockReturnValue(cho.promise)
    const lanDau = trang.luuHoSo()
    await trang.luuHoSo()
    expect(xacThucService.suaHoSo).toHaveBeenCalledTimes(1)
    cho.resolve({ data: { ...khach, ho_ten: 'Mới', updated_at: 'mới' }, message: 'Đã lưu' })
    await lanDau
    expect(useXacThucStore().taiKhoan.ho_ten).toBe('Mới')
    expect(trang.phienBan).toBe('mới')
    expect(trang.dangLuu).toBe(false)
  })
  it('409 giữ bản nhập, chặn ghi tiếp và chỉ thay khi tải lại', async () => {
    const trang = trangHoSo()
    trang.bieuMau.ho_ten = 'Bản nhập'
    xacThucService.suaHoSo.mockRejectedValue({
      response: { status: 409, data: { message: 'Bản cũ' } },
    })
    await trang.luuHoSo()
    await trang.luuHoSo()
    expect(trang.bieuMau.ho_ten).toBe('Bản nhập')
    expect(xacThucService.suaHoSo).toHaveBeenCalledTimes(1)
    xacThucService.taiTaiKhoan.mockResolvedValue({
      data: { ...khach, ho_ten: 'Trên server', updated_at: 'mới' },
    })
    await trang.taiLai()
    expect(trang.bieuMau.ho_ten).toBe('Trên server')
    expect(trang.xungDot).toBe(false)
  })
  it('422, mất mạng và 419 giữ biểu mẫu; response sau unmount không ghi store', async () => {
    const trang = trangHoSo()
    trang.bieuMau.ho_ten = 'Đang nhập'
    xacThucService.suaHoSo.mockRejectedValue({
      response: { status: 422, data: { errors: { 'thoi_gian_co_the_tap.0': ['Quá dài'] } } },
    })
    await trang.luuHoSo()
    expect(trang.loiThoiGian).toEqual(['Quá dài'])
    xacThucService.suaHoSo.mockRejectedValue(new Error('Mạng'))
    await trang.luuHoSo()
    expect(trang.bieuMau.ho_ten).toBe('Đang nhập')
    xacThucService.suaHoSo.mockRejectedValue({ response: { status: 419 } })
    await trang.luuHoSo()
    expect(trang.canDangNhap).toBe(true)
    const cho = choKetQua()
    const cu = trangHoSo()
    xacThucService.suaHoSo.mockReturnValue(cho.promise)
    const lan = cu.luuHoSo()
    SuaHoSo.beforeUnmount.call(cu)
    cho.resolve({ data: { ...khach, ho_ten: 'Muộn' }, message: 'Lưu' })
    await lan
    expect(useXacThucStore().taiKhoan.ho_ten).toBe(khach.ho_ten)
  })
  it('token chỉ đọc từ fragment, xóa URL và không chấp nhận token thiếu/sai', () => {
    const trang = trangReset()
    expect(trang.tokenHopLe).toBe(true)
    expect(trang.email).toBe(khach.email)
    expect(trang.$router.replace).toHaveBeenCalledWith({
      path: '/dat-lai-mat-khau',
      hash: '',
      query: {},
    })
    const sai = taoTrang(KhoiPhuc, {
      $route: { path: '/dat-lai-mat-khau', hash: '#token=sai&email=x' },
    })
    sai.khoiTao()
    expect(sai.tokenHopLe).toBe(false)
  })
  it('reset chỉ gửi email/token/mật khẩu, chặn lặp, thành công xóa token và mật khẩu', async () => {
    const trang = trangReset()
    trang.password = 'MatKhauMoi123!'
    trang.password_confirmation = trang.password
    const cho = choKetQua()
    xacThucService.datLaiMatKhau.mockReturnValue(cho.promise)
    const lan = trang.gui()
    await trang.gui()
    expect(xacThucService.datLaiMatKhau).toHaveBeenCalledOnce()
    expect(xacThucService.datLaiMatKhau).toHaveBeenCalledWith({
      email: khach.email,
      token,
      password: 'MatKhauMoi123!',
      password_confirmation: 'MatKhauMoi123!',
    })
    cho.resolve({ message: 'Đã đặt lại' })
    await lan
    expect(trang.daXong).toBe(true)
    expect(trang.token).toBe('')
    expect(trang.password).toBe('')
    expect(useXacThucStore().taiKhoan).toEqual(khach)
  })
  it('reset lỗi không mất bản nhập; chuyển sang quên mật khẩu bỏ response cũ', async () => {
    const trang = trangReset()
    trang.password = 'Đang nhập'
    xacThucService.datLaiMatKhau.mockRejectedValue({
      response: { status: 422, data: { errors: { token: ['Hết hạn'] } } },
    })
    await trang.gui()
    expect(trang.password).toBe('Đang nhập')
    expect(trang.loiTruong.token).toEqual(['Hết hạn'])
    const cho = choKetQua()
    xacThucService.datLaiMatKhau.mockReturnValue(cho.promise)
    const lan = trang.gui()
    trang.$route = { path: '/quen-mat-khau', hash: '' }
    trang.khoiTao()
    cho.resolve({ message: 'Muộn' })
    await lan
    expect(trang.thongBao).toBe('')
    expect(trang.daXong).toBe(false)
    expect(trang.token).toBe('')
  })
  it('quên mật khẩu chuẩn hóa email, chặn double-submit và chịu được 503', async () => {
    const trang = taoTrang(KhoiPhuc, { $route: { path: '/quen-mat-khau', hash: '' } })
    trang.email = ' KHACH@Example.Test '
    const cho = choKetQua()
    xacThucService.guiLienKet.mockReturnValue(cho.promise)
    const lan = trang.gui()
    await trang.gui()
    expect(xacThucService.guiLienKet).toHaveBeenCalledWith({ email: khach.email })
    expect(xacThucService.guiLienKet).toHaveBeenCalledOnce()
    cho.resolve({ message: 'Nếu tồn tại…' })
    await lan
    expect(trang.thongBao).toBe('Nếu tồn tại…')
    xacThucService.guiLienKet.mockRejectedValue({
      response: { status: 503, data: { message: 'Chưa sẵn sàng' } },
    })
    await trang.gui()
    expect(trang.email).toBe(' KHACH@Example.Test ')
    expect(trang.coLoi).toBe(true)
  })
  it('khóa qua dialog dùng bản sao và version, không tự khóa hoặc submit hai lần', async () => {
    const trang = taoTrang(TaiKhoan)
    const hang = { ...khach, id: 7 }
    trang.danhSach = [hang]
    await trang.moXacNhan(khach)
    expect(trang.$refs.xacNhan.showModal).not.toHaveBeenCalled()
    await trang.moXacNhan(hang)
    expect(trang.taiKhoanDoi).not.toBe(hang)
    const cho = choKetQua()
    taiKhoanService.datTrangThai.mockReturnValue(cho.promise)
    const lan = trang.xacNhanTrangThai()
    trang.dongXacNhan()
    await trang.xacNhanTrangThai()
    expect(trang.taiKhoanDoi).not.toBeNull()
    expect(taiKhoanService.datTrangThai).toHaveBeenCalledOnce()
    expect(taiKhoanService.datTrangThai).toHaveBeenCalledWith(7, {
      trang_thai: 'BI_KHOA',
      updated_at: khach.updated_at,
    })
    cho.resolve({ data: { ...hang, trang_thai: 'BI_KHOA' }, message: 'Khóa' })
    await lan
    expect(trang.danhSach[0].trang_thai).toBe('BI_KHOA')
    expect(trang.$refs.xacNhan.close).toHaveBeenCalledOnce()
  })
  it('khóa bản cũ không retry tự động, đóng dialog và tải danh sách mới', async () => {
    const trang = taoTrang(TaiKhoan)
    await trang.moXacNhan({ ...khach, id: 7 })
    taiKhoanService.datTrangThai.mockRejectedValue({
      response: { status: 409, data: { message: 'Đã đổi' } },
    })
    taiKhoanService.taiDanhSach.mockResolvedValue({
      data: [{ ...khach, id: 7, trang_thai: 'BI_KHOA' }],
      meta: { current_page: 1 },
    })
    await trang.xacNhanTrangThai()
    expect(taiKhoanService.datTrangThai).toHaveBeenCalledOnce()
    expect(trang.danhSach[0].trang_thai).toBe('BI_KHOA')
    expect(trang.taiKhoanDoi).toBeNull()
    expect(trang.coLoi).toBe(true)
  })
})
