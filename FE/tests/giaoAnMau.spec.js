import { beforeEach, describe, expect, it, vi } from 'vitest'
import { danhSoThuTu, doiThuTu, taoNoiDung } from '../src/utils/giaoAnMau'
import BieuMau from '../src/views/Admin/GiaoAnMau/BieuMau/index.vue'
import DanhSach from '../src/components/DanhSachGiaoAnMau.vue'
import ChonBai from '../src/components/ChonBaiTapGiaoAn.vue'
import ChiTiet from '../src/views/PT/GiaoAnMau/ChiTiet/index.vue'
import giaoAnMauService from '../src/services/giaoAnMauService'
import baiTapService from '../src/services/baiTapService'

vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/services/giaoAnMauService', () => ({
  default: {
    taiDanhSach: vi.fn(),
    taiChiTiet: vi.fn(),
    taoGiaoAn: vi.fn(),
    suaGiaoAn: vi.fn(),
    datTrangThai: vi.fn(),
  },
}))
vi.mock('../src/services/baiTapService', () => ({ default: { taiDanhSach: vi.fn() } }))

function choKetQua() {
  let resolve
  const promise = new Promise((thanhCong) => {
    resolve = thanhCong
  })
  return { promise, resolve }
}
function trangBieuMau(ghiDe = {}) {
  const trang = {
    ...BieuMau.data(),
    $route: { path: '/admin/giao-an-mau/them', params: {} },
    $router: { replace: vi.fn() },
    $nextTick: async () => {},
    $refs: {},
    ...ghiDe,
  }
  for (const [ten, ham] of Object.entries(BieuMau.methods)) trang[ten] = ham.bind(trang)
  Object.defineProperty(trang, 'coThayDoi', { get: () => BieuMau.computed.coThayDoi.call(trang) })
  return trang
}
const giaoAn = {
  id: 7,
  ten_giao_an: 'Toàn thân',
  muc_tieu: 'Tăng cơ',
  so_ngay_tap: 1,
  trang_thai: 'NHAP',
  updated_at: '2026-10-01 10:00:00.000001',
  bai_tap: [],
}

describe('Giáo án mẫu', () => {
  beforeEach(() => vi.resetAllMocks())
  it('đổi thứ tự trong đúng ngày, không sửa mảng đầu vào hoặc vượt ranh giới ngày', () => {
    const cacBai = [
      { khoa: 'a', ngay_thu: 1, thu_tu: 1 },
      { khoa: 'c', ngay_thu: 2, thu_tu: 1 },
      { khoa: 'b', ngay_thu: 1, thu_tu: 2 },
    ]
    const banDau = structuredClone(cacBai)
    expect(doiThuTu(cacBai, 'b', -1).map((bai) => [bai.khoa, bai.thu_tu])).toEqual([
      ['b', 1],
      ['a', 2],
      ['c', 1],
    ])
    expect(doiThuTu(cacBai, 'a', -1)).toEqual(cacBai)
    expect(doiThuTu(cacBai, 'c', 1)).toEqual(cacBai)
    expect(cacBai).toEqual(banDau)
    expect(danhSoThuTu(cacBai.filter((bai) => bai.khoa !== 'a'))).toMatchObject([
      { khoa: 'b', thu_tu: 1 },
      { khoa: 'c', thu_tu: 1 },
    ])
  })
  it('payload chỉ chứa dữ liệu nghiệp vụ, bỏ role/duyệt/dữ liệu trình bày và ép số', () => {
    const duLieu = taoNoiDung(
      { ten_giao_an: ' Toàn thân ', muc_tieu: ' ', so_ngay_tap: '1', nguoi_duyet_id: 99 },
      [
        {
          khoa: 'key',
          bai_tap_id: '12',
          ngay_thu: '1',
          thu_tu: 8,
          so_hiep: '3',
          so_lan_lap: '12',
          nghi_giay: '0',
          ghi_chu: ' 0 ',
          ten_bai_tap: 'Hiển thị',
          kha_dung: true,
        },
      ],
    )
    expect(duLieu).toEqual({
      ten_giao_an: 'Toàn thân',
      muc_tieu: null,
      so_ngay_tap: 1,
      bai_tap: [
        {
          bai_tap_id: 12,
          ngay_thu: 1,
          thu_tu: 1,
          so_hiep: 3,
          so_lan_lap: 12,
          nghi_giay: 0,
          ghi_chu: '0',
        },
      ],
    })
  })
  it('giảm ngày không tự xóa bài và thêm/bỏ bài đánh lại thứ tự đúng ngày', () => {
    const trang = trangBieuMau({
      ngayChon: 2,
      soNgayNhap: 1,
      bieuMau: { ten_giao_an: '', muc_tieu: '', so_ngay_tap: 2 },
    })
    trang.themBai({ id: 3, ten_bai_tap: 'Chống đẩy', nhom_co: { ten_nhom_co: 'Ngực' } })
    trang.doiSoNgay()
    expect(trang.bieuMau.so_ngay_tap).toBe(2)
    expect(trang.soNgayNhap).toBe(2)
    expect(trang.cacBai).toHaveLength(1)
    trang.boBai(trang.cacBai[0].khoa)
    trang.soNgayNhap = 1
    trang.doiSoNgay()
    expect(trang.bieuMau.so_ngay_tap).toBe(1)
    expect(trang.ngayChon).toBe(1)
  })
  it('mất mạng giữ UUID và nội dung, gửi trùng bị chặn', async () => {
    const cho = choKetQua()
    giaoAnMauService.taoGiaoAn
      .mockReturnValueOnce(cho.promise)
      .mockRejectedValueOnce(new Error('mất mạng'))
    const trang = trangBieuMau()
    trang.bieuMau.ten_giao_an = 'Đang soạn'
    const lanDau = trang.luuGiaoAn()
    await trang.luuGiaoAn()
    expect(giaoAnMauService.taoGiaoAn).toHaveBeenCalledTimes(1)
    const uuid = trang.maYeuCau
    cho.resolve({ data: giaoAn, message: 'Đã lưu' })
    await lanDau
    expect(trang.phienBan).toBe(giaoAn.updated_at)
    expect(trang.$router.replace).toHaveBeenCalledWith('/admin/giao-an-mau/7/sua')
    const mang = trangBieuMau()
    mang.bieuMau.ten_giao_an = 'Chưa mất'
    const maCu = mang.maYeuCau
    await mang.luuGiaoAn()
    expect(mang.bieuMau.ten_giao_an).toBe('Chưa mất')
    expect(mang.maYeuCau).toBe(maCu)
    expect(giaoAnMauService.taoGiaoAn.mock.calls[0][0].client_request_id).toBe(uuid)
    expect(mang.dangLuu).toBe(false)
  })
  it('409 giữ form và khóa ghi tiếp; tải lại lấy phiên bản mới', async () => {
    giaoAnMauService.suaGiaoAn.mockRejectedValue({
      response: { status: 409, data: { message: 'Bản cũ.' } },
    })
    const trang = trangBieuMau({
      $route: { path: '/admin/giao-an-mau/7/sua', params: { id: '7' } },
    })
    trang.napDuLieu(giaoAn)
    trang.bieuMau.ten_giao_an = 'Nội dung đang soạn'
    await trang.luuGiaoAn()
    await trang.luuGiaoAn()
    expect(trang.canTaiLai).toBe(true)
    expect(trang.bieuMau.ten_giao_an).toBe('Nội dung đang soạn')
    expect(giaoAnMauService.suaGiaoAn).toHaveBeenCalledTimes(1)
    giaoAnMauService.taiChiTiet.mockResolvedValue({ data: { ...giaoAn, updated_at: 'mới' } })
    await trang.taiGiaoAn()
    expect(trang.canTaiLai).toBe(false)
    expect(trang.phienBan).toBe('mới')
    expect(trang.coThayDoi).toBe(false)
  })
  it('duyệt cần bản đã lưu; lỗi 422 giữ dữ liệu và nhận trạng thái/phiên bản server', async () => {
    const trang = trangBieuMau()
    trang.napDuLieu(giaoAn)
    trang.bieuMau.muc_tieu = 'Đổi'
    await trang.datTrangThai('DA_DUYET')
    expect(giaoAnMauService.datTrangThai).not.toHaveBeenCalled()
    trang.napDuLieu(giaoAn)
    giaoAnMauService.datTrangThai
      .mockRejectedValueOnce({
        response: {
          status: 422,
          data: { message: 'Thiếu bài.', errors: { bai_tap: ['Mỗi ngày cần bài.'] } },
        },
      })
      .mockResolvedValueOnce({
        data: { ...giaoAn, trang_thai: 'DA_DUYET', updated_at: 'phiên-bản-duyệt' },
        message: 'Đã duyệt',
      })
    await trang.datTrangThai('DA_DUYET')
    expect(trang.loiTruong.bai_tap).toEqual(['Mỗi ngày cần bài.'])
    expect(trang.id).toBe(7)
    await trang.datTrangThai('DA_DUYET')
    expect(trang.trangThai).toBe('DA_DUYET')
    expect(trang.phienBan).toBe('phiên-bản-duyệt')
    expect(giaoAnMauService.datTrangThai.mock.calls[0][1]).toEqual({
      trang_thai: 'DA_DUYET',
      updated_at: giaoAn.updated_at,
    })
  })
  it('kết quả ghi đến sau đổi trang không ghi đè form mới', async () => {
    const cho = choKetQua()
    giaoAnMauService.taoGiaoAn.mockReturnValue(cho.promise)
    const trang = trangBieuMau()
    const luu = trang.luuGiaoAn()
    trang.$route = { path: '/admin/giao-an-mau/9/sua', params: { id: '9' } }
    trang.id = 9
    cho.resolve({ data: giaoAn })
    await luu
    expect(trang.id).toBe(9)
    expect(trang.$router.replace).not.toHaveBeenCalled()
  })
  it('danh sách PT không gửi bộ lọc trạng thái và bỏ response cũ', async () => {
    const cu = choKetQua()
    const moi = choKetQua()
    giaoAnMauService.taiDanhSach.mockReturnValueOnce(cu.promise).mockReturnValueOnce(moi.promise)
    const trang = { ...DanhSach.data(), quanTri: false, trangThai: 'NHAP' }
    const lanCu = DanhSach.methods.taiDanhSach.call(trang)
    const lanMoi = DanhSach.methods.taiDanhSach.call(trang)
    expect(giaoAnMauService.taiDanhSach.mock.calls[0][0]).not.toHaveProperty('trang_thai')
    expect(giaoAnMauService.taiDanhSach.mock.calls[0][1].aborted).toBe(true)
    moi.resolve({ data: [{ id: 2 }], meta: { total: 1 } })
    await lanMoi
    cu.resolve({ data: [{ id: 1 }], meta: { total: 99 } })
    await lanCu
    expect(trang.danhSach).toEqual([{ id: 2 }])
    expect(trang.dangTai).toBe(false)
  })
  it('chọn bài tìm theo trang, response cũ không thay kết quả mới', async () => {
    const cu = choKetQua()
    baiTapService.taiDanhSach
      .mockReturnValueOnce(cu.promise)
      .mockResolvedValueOnce({ data: [{ id: 2 }], meta: { last_page: 2 } })
    const trang = { ...ChonBai.data(), tuKhoa: ' Đẩy ', page: 2 }
    const lanCu = ChonBai.methods.taiBaiTap.call(trang)
    await ChonBai.methods.taiBaiTap.call(trang)
    cu.resolve({ data: [{ id: 1 }], meta: { last_page: 100 } })
    await lanCu
    expect(trang.danhSach).toEqual([{ id: 2 }])
    expect(baiTapService.taiDanhSach.mock.calls[1][0]).toEqual({
      tu_khoa: 'Đẩy',
      page: 2,
      per_page: 6,
    })
  })
  it('PT nhận 404 khi giáo án bị ngừng; không giữ nội dung bản cũ', async () => {
    giaoAnMauService.taiChiTiet.mockRejectedValue({ response: { status: 404, data: {} } })
    const trang = { ...ChiTiet.data(), giaoAn, $route: { params: { id: '7' } } }
    await ChiTiet.methods.taiGiaoAn.call(trang)
    expect(trang.giaoAn).toBeNull()
    expect(trang.loi).toContain('không còn được duyệt')
    expect(trang.dangTai).toBe(false)
    giaoAnMauService.taiChiTiet.mockRejectedValue({
      response: { status: 401, data: { message: 'Phiên hết hạn' } },
    })
    await ChiTiet.methods.taiGiaoAn.call(trang)
    expect(trang.hetPhien).toBe(true)
    expect(trang.giaoAn).toBeNull()
  })
})
