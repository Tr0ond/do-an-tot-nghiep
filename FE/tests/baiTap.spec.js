import { describe, expect, it, vi } from 'vitest'
import { docBoLoc, taoQueryBoLoc, taoUrlMedia } from '../src/utils/baiTap'
import DanhSachBaiTap from '../src/views/BaiTap/index.vue'
import ChiTietBaiTap from '../src/views/BaiTap/ChiTiet/index.vue'
import baiTapService from '../src/services/baiTapService'

vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/services/baiTapService', () => ({
  default: { taiBoLoc: vi.fn(), taiDanhSach: vi.fn(), taiChiTiet: vi.fn(), urlMedia: vi.fn() },
}))

function taoPromiseCho() {
  let thanhCong
  let thatBai
  const promise = new Promise((resolve, reject) => {
    thanhCong = resolve
    thatBai = reject
  })
  return { promise, thanhCong, thatBai }
}

describe('Bộ lọc và đường dẫn media', () => {
  it('khôi phục bộ lọc và trang từ URL', () => {
    expect(
      docBoLoc({ tu_khoa: 'sit-up', nhom_co_id: '2', dung_cu_nguon: 'body weight', page: '3' }),
    ).toEqual({ tu_khoa: 'sit-up', nhom_co_id: '2', dung_cu_nguon: 'body weight', page: 3 })
  })
  it('bỏ giá trị query dạng mảng và trang không hợp lệ', () => {
    expect(docBoLoc({ tu_khoa: ['a', 'b'], page: 'no' }).tu_khoa).toBe('')
    for (const page of ['0', '-1', '1.5', '100001']) expect(docBoLoc({ page }).page).toBe(1)
  })
  it('tìm kiếm mới đưa về trang 1 và bỏ bộ lọc rỗng', () => {
    const boLoc = { tu_khoa: '  sit-up  ', nhom_co_id: '', dung_cu_nguon: '', page: 4 }
    expect(taoQueryBoLoc(boLoc)).toEqual({ tu_khoa: 'sit-up' })
    expect(taoQueryBoLoc(boLoc, 2)).toEqual({ tu_khoa: 'sit-up', page: '2' })
    expect(taoQueryBoLoc(docBoLoc())).toEqual({})
  })
  it('media ghép vào origin Backend khi Frontend chạy riêng', () => {
    expect(taoUrlMedia('/media/bai-tap/images/0001-2gPfomN.jpg', 'http://localhost:8000')).toBe(
      'http://localhost:8000/media/bai-tap/images/0001-2gPfomN.jpg',
    )
  })
  it.each([
    'https://other.test/image.jpg',
    '//other.test/x.gif',
    '/media/bai-tap/images/../x.jpg',
    'javascript:alert(1)',
    null,
  ])('không dùng media ngoài thư mục catalog: %s', (duongDan) => {
    expect(taoUrlMedia(duongDan, 'http://localhost:8000')).toBe('')
  })
})

describe('Yêu cầu bài tập thay đổi nhanh', () => {
  it('kết quả cũ tới muộn không ghi đè bộ lọc mới', async () => {
    vi.resetAllMocks()
    const cu = taoPromiseCho()
    const moi = taoPromiseCho()
    baiTapService.taiDanhSach.mockReturnValueOnce(cu.promise).mockReturnValueOnce(moi.promise)
    const trang = { ...DanhSachBaiTap.data(), $route: { query: {} } }
    const lanCu = DanhSachBaiTap.methods.taiDanhSach.call(trang)
    const signalCu = baiTapService.taiDanhSach.mock.calls[0][1]
    trang.$route.query = { tu_khoa: 'moi' }
    const lanMoi = DanhSachBaiTap.methods.taiDanhSach.call(trang)
    expect(signalCu.aborted).toBe(true)
    moi.thanhCong({ data: [{ id: 2 }], meta: { total: 1 } })
    await lanMoi
    cu.thanhCong({ data: [{ id: 1 }], meta: { total: 99 } })
    await lanCu
    expect(trang.danhSach).toEqual([{ id: 2 }])
    expect(trang.phanTrang.total).toBe(1)
    expect(trang.dangTai).toBe(false)
  })
  it('lỗi của yêu cầu đã hủy không che kết quả mới', async () => {
    vi.resetAllMocks()
    const cu = taoPromiseCho()
    baiTapService.taiDanhSach
      .mockReturnValueOnce(cu.promise)
      .mockResolvedValueOnce({ data: [{ id: 3 }], meta: { total: 1 } })
    const trang = { ...DanhSachBaiTap.data(), $route: { query: {} } }
    const lanCu = DanhSachBaiTap.methods.taiDanhSach.call(trang)
    await DanhSachBaiTap.methods.taiDanhSach.call(trang)
    cu.thatBai(new Error('Yêu cầu cũ bị hủy'))
    await lanCu
    expect(trang.thongBao).toBe('')
    expect(trang.danhSach).toEqual([{ id: 3 }])
  })
  it('mất mạng kết thúc loading và có thông báo để thử lại', async () => {
    vi.resetAllMocks()
    baiTapService.taiDanhSach.mockRejectedValue(new Error('Mất mạng'))
    const trang = { ...DanhSachBaiTap.data(), $route: { query: {} } }
    await DanhSachBaiTap.methods.taiDanhSach.call(trang)
    expect(trang.dangTai).toBe(false)
    expect(trang.thongBao).toContain('Không thể kết nối')
  })
  it('đóng trang hủy request và không cập nhật kết quả về sau', async () => {
    vi.resetAllMocks()
    const cho = taoPromiseCho()
    baiTapService.taiDanhSach.mockReturnValue(cho.promise)
    const trang = { ...DanhSachBaiTap.data(), $route: { query: {} } }
    const dangCho = DanhSachBaiTap.methods.taiDanhSach.call(trang)
    DanhSachBaiTap.beforeUnmount.call(trang)
    expect(trang.huyYeuCau.signal.aborted).toBe(true)
    cho.thanhCong({ data: [{ id: 5 }], meta: { total: 1 } })
    await dangCho
    expect(trang.danhSach).toEqual([])
  })
  it('chỉ cuộn tới kết quả sau khi tải xong, tránh cuộn khi trang còn bị thu ngắn bởi loading', async () => {
    vi.resetAllMocks()
    const cho = taoPromiseCho()
    const cuon = vi.fn()
    baiTapService.taiDanhSach.mockReturnValue(cho.promise)
    const trang = {
      ...DanhSachBaiTap.data(),
      canCuonKetQua: true,
      $route: { query: { page: '2' } },
      $refs: { ketQua: { scrollIntoView: cuon } },
      $nextTick: async () => {},
    }
    trang.cuonDenKetQua = DanhSachBaiTap.methods.cuonDenKetQua.bind(trang)
    const dangCho = DanhSachBaiTap.methods.taiDanhSach.call(trang)
    await trang.cuonDenKetQua()
    expect(cuon).not.toHaveBeenCalled()
    cho.thanhCong({ data: [{ id: 13 }], meta: { current_page: 2, total: 1324 } })
    await dangCho
    expect(trang.dangTai).toBe(false)
    expect(cuon).toHaveBeenCalledOnce()
    expect(trang.canCuonKetQua).toBe(false)
  })
  it('đổi bài tập dừng GIF và không dùng dữ liệu chi tiết cũ', async () => {
    vi.resetAllMocks()
    baiTapService.taiChiTiet.mockRejectedValue({ response: { status: 404 } })
    const trang = {
      ...ChiTietBaiTap.data(),
      baiTap: { id: 1 },
      dangXemGif: true,
      $route: { params: { id: '99' } },
    }
    await ChiTietBaiTap.methods.taiChiTiet.call(trang)
    expect(trang.baiTap).toBeNull()
    expect(trang.dangXemGif).toBe(false)
    expect(trang.khongTimThay).toBe(true)
    expect(trang.dangTai).toBe(false)
  })
})
