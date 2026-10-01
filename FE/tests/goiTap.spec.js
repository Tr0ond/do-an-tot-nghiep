import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  dinhDangGia,
  docBoLocGoiTap,
  taoQueryGoiTap,
  taoBieuMauGoiTap,
  taoPayloadGoiTap,
} from '../src/utils/goiTap'
import DanhSach from '../src/components/DanhSachGoiTap.vue'
import ChiTiet from '../src/views/GoiTap/ChiTiet/index.vue'
import BieuMau from '../src/views/Admin/GoiTap/BieuMau/index.vue'
import goiTapService from '../src/services/goiTapService'

vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/services/goiTapService', () => ({
  default: {
    taiDanhSach: vi.fn(),
    taiChiTiet: vi.fn(),
    taoGoiTap: vi.fn(),
    suaGoiTap: vi.fn(),
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
function trangBieuMau(ghiDe = {}) {
  return {
    ...BieuMau.data(),
    laThem: true,
    maYeuCau: 'uuid-giu-nguyen',
    $nextTick: async () => {},
    $refs: {},
    $router: { replace: vi.fn() },
    ...ghiDe,
  }
}
describe('Danh mục gói tập', () => {
  beforeEach(() => vi.resetAllMocks())

  it('URL công khai loại bỏ trạng thái admin và giữ loại/page hợp lệ', () => {
    const boLoc = docBoLocGoiTap({
      tu_khoa: ' PT ',
      loai_goi: 'PT_CHATBOT',
      trang_thai: 'NGUNG_SU_DUNG',
      page: '3',
    })
    expect(boLoc).not.toHaveProperty('trang_thai')
    expect(taoQueryGoiTap(boLoc, 3)).toEqual({ tu_khoa: 'PT', loai_goi: 'PT_CHATBOT', page: '3' })
    expect(taoQueryGoiTap(boLoc)).not.toHaveProperty('page')
    expect(docBoLocGoiTap({ loai_goi: ['CHATBOT'], page: '100001' })).toMatchObject({
      loai_goi: '',
      page: 1,
    })
    expect(docBoLocGoiTap({ trang_thai: 'NGUNG_SU_DUNG' }, true).trang_thai).toBe('NGUNG_SU_DUNG')
  })

  it('payload chatbot bỏ buổi PT cũ, sửa không gửi trạng thái/UUID hay snapshot', () => {
    const goi = { ten_goi: 'Gói thật', gia: 99000, so_buoi_pt: 12, updated_at: 'phien-ban-cu' }
    const form = taoBieuMauGoiTap(goi)
    form.loai_goi = 'CHATBOT'
    form.ten_goi = ' Gói mới '
    expect(goi.ten_goi).toBe('Gói thật')
    expect(taoPayloadGoiTap(form, goi, 'uuid')).toMatchObject({
      ten_goi: 'Gói mới',
      so_buoi_pt: 0,
      co_chatbot: true,
      updated_at: 'phien-ban-cu',
    })
    for (const truong of ['client_request_id', 'trang_thai', 'gia_snapshot'])
      expect(taoPayloadGoiTap(form, goi, 'uuid')).not.toHaveProperty(truong)
    expect(taoPayloadGoiTap(taoBieuMauGoiTap(), null, 'uuid')).toMatchObject({
      trang_thai: 'NGUNG_SU_DUNG',
      client_request_id: 'uuid',
    })
  })

  it('giá toàn gói định dạng VND nguyên, không bịa khi dữ liệu trống', () => {
    expect(dinhDangGia(99000)).toBe('99.000 ₫')
    expect(dinhDangGia('')).toBe('— ₫')
    expect(dinhDangGia('9007199254740991')).toBe('9.007.199.254.740.991 ₫')
    expect(dinhDangGia('1.5')).toBe('— ₫')
  })

  it('danh sách bỏ response cũ và abort khi đổi bộ lọc', async () => {
    const cu = choKetQua()
    const moi = choKetQua()
    goiTapService.taiDanhSach.mockReturnValueOnce(cu.promise).mockReturnValueOnce(moi.promise)
    const trang = {
      ...DanhSach.data.call({ $route: { query: {} }, quanTri: false }),
      quanTri: false,
      $route: { query: {} },
    }
    const lanCu = DanhSach.methods.taiDanhSach.call(trang)
    const signalCu = goiTapService.taiDanhSach.mock.calls[0][1]
    const lanMoi = DanhSach.methods.taiDanhSach.call(trang)
    expect(signalCu.aborted).toBe(true)
    moi.resolve({ data: [{ id: 2 }], meta: { total: 1 } })
    await lanMoi
    cu.resolve({ data: [{ id: 1 }], meta: { total: 90 } })
    await lanCu
    expect(trang.danhSach).toEqual([{ id: 2 }])
    expect(trang.meta.total).toBe(1)
  })

  it('chi tiết xử lý 404 và không giữ gói cũ khi đổi ID', async () => {
    goiTapService.taiChiTiet.mockRejectedValue({
      response: { status: 404, data: { message: 'Không tìm thấy.' } },
    })
    const trang = { ...ChiTiet.data(), goi: { id: 1 }, $route: { params: { id: '2' } } }
    await ChiTiet.methods.taiChiTiet.call(trang)
    expect(trang.goi).toBeNull()
    expect(trang.khongTimThay).toBe(true)
    expect(trang.dangTai).toBe(false)
  })

  it('lỗi mạng khi tạo giữ UUID/nội dung để retry, không double-submit', async () => {
    const cho = choKetQua()
    goiTapService.taoGoiTap
      .mockReturnValueOnce(cho.promise)
      .mockRejectedValueOnce(new Error('mang-loi'))
    const trang = trangBieuMau()
    trang.duLieu.ten_goi = 'Gói đang soạn'
    const lanLuu = BieuMau.methods.luuGoiTap.call(trang)
    await BieuMau.methods.luuGoiTap.call(trang)
    expect(goiTapService.taoGoiTap).toHaveBeenCalledTimes(1)
    cho.resolve({ data: { id: 7 } })
    await lanLuu
    expect(trang.$router.replace).toHaveBeenCalledWith('/admin/goi-tap/7/sua')
    await BieuMau.methods.luuGoiTap.call(trang)
    expect(trang.maYeuCau).toBe('uuid-giu-nguyen')
    expect(trang.duLieu.ten_goi).toBe('Gói đang soạn')
    expect(goiTapService.taoGoiTap.mock.calls[1][0].client_request_id).toBe(
      goiTapService.taoGoiTap.mock.calls[0][0].client_request_id,
    )
    expect(trang.xungDot).toBe(false)
  })

  it('409 giữ nội dung và khóa lưu tới khi tải lại', async () => {
    goiTapService.suaGoiTap.mockRejectedValue({
      response: { status: 409, data: { message: 'Đã đổi.' } },
    })
    const trang = trangBieuMau({ goi: { id: 1, updated_at: 'cu' }, laThem: false })
    trang.duLieu.ten_goi = 'Đang soạn'
    await BieuMau.methods.luuGoiTap.call(trang)
    await BieuMau.methods.luuGoiTap.call(trang)
    expect(trang.xungDot).toBe(true)
    expect(trang.duLieu.ten_goi).toBe('Đang soạn')
    expect(goiTapService.suaGoiTap).toHaveBeenCalledTimes(1)
  })

  it('lưu xong nhận phiên bản mới, response lưu cũ sau đổi route bị bỏ', async () => {
    goiTapService.suaGoiTap.mockResolvedValue({
      data: { id: 1, ten_goi: 'Mới', updated_at: 'moi' },
      message: 'Đã lưu',
    })
    const trang = trangBieuMau({ goi: { id: 1, updated_at: 'cu' }, laThem: false })
    await BieuMau.methods.luuGoiTap.call(trang)
    expect(trang.goi.updated_at).toBe('moi')
    const cho = choKetQua()
    goiTapService.suaGoiTap.mockReturnValue(cho.promise)
    const lanLuu = BieuMau.methods.luuGoiTap.call(trang)
    trang.lanTai++
    trang.goi = { id: 2, updated_at: 'goi-khac' }
    cho.resolve({ data: { id: 1, updated_at: 'qua-cu' }, message: 'Đã lưu' })
    await lanLuu
    expect(trang.goi.id).toBe(2)
  })

  it('public không đổi trạng thái, Admin chặn nhấn kép và gửi phiên bản server', async () => {
    const cho = choKetQua()
    goiTapService.datTrangThai.mockReturnValue(cho.promise)
    const trang = { quanTri: false, dangDoi: null, daDong: false, taiDanhSach: vi.fn() }
    const goi = { id: 1, trang_thai: 'HOAT_DONG', updated_at: 'phien-ban' }
    await DanhSach.methods.doiTrangThai.call(trang, goi)
    expect(goiTapService.datTrangThai).not.toHaveBeenCalled()
    trang.quanTri = true
    const lanDoi = DanhSach.methods.doiTrangThai.call(trang, goi)
    await DanhSach.methods.doiTrangThai.call(trang, goi)
    expect(goiTapService.datTrangThai).toHaveBeenCalledTimes(1)
    expect(goiTapService.datTrangThai).toHaveBeenCalledWith(1, {
      trang_thai: 'NGUNG_SU_DUNG',
      updated_at: 'phien-ban',
    })
    cho.resolve({ message: 'Đã ngừng bán' })
    await lanDoi
    expect(trang.taiDanhSach).toHaveBeenCalledTimes(1)
  })
})
