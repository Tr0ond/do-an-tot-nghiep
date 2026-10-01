import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  docBoLocAdmin,
  taoQueryAdmin,
  taoBieuMauBaiTap,
  taoPayloadBaiTap,
} from '../src/utils/baiTapAdmin'
import DanhSach from '../src/views/Admin/BaiTap/index.vue'
import BieuMau from '../src/views/Admin/BaiTap/BieuMau/index.vue'
import baiTapAdminService from '../src/services/baiTapAdminService'

vi.mock('../src/services/xacThucService', () => ({ default: {} }))
vi.mock('../src/services/baiTapService', () => ({ default: { urlMedia: vi.fn() } }))
vi.mock('../src/services/baiTapAdminService', () => ({
  default: {
    taiBoLoc: vi.fn(),
    taiDanhSach: vi.fn(),
    taiChiTiet: vi.fn(),
    taoBaiTap: vi.fn(),
    suaBaiTap: vi.fn(),
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

describe('Biên tập bài tập Admin', () => {
  beforeEach(() => vi.resetAllMocks())

  it('URL giữ trạng thái/trang và tìm mới về trang 1', () => {
    const boLoc = docBoLocAdmin({ tu_khoa: '  Gập bụng ', trang_thai: 'NGUNG_SU_DUNG', page: '3' })
    expect(boLoc.page).toBe(3)
    expect(taoQueryAdmin(boLoc)).toEqual({ tu_khoa: 'Gập bụng', trang_thai: 'NGUNG_SU_DUNG' })
    expect(taoQueryAdmin(boLoc, 3).page).toBe('3')
    expect(docBoLocAdmin({ trang_thai: ['HOAT_DONG'], page: 'bad' })).toMatchObject({
      trang_thai: '',
      page: 1,
    })
  })

  it('form là bản sao; payload sửa dataset không gửi nguồn/media/tên gốc', () => {
    const baiTap = {
      id: 1,
      ma_nguon: '0001',
      nguon_du_lieu: 'exercises-dataset',
      ten_bai_tap: 'Sit-up',
      cac_buoc_vi: ['Một', 'Hai'],
      updated_at: '2026-10-01 00:00:00.123456',
    }
    const form = taoBieuMauBaiTap(baiTap)
    form.cac_buoc_vi = ' Bước mới \n\n Bước hai '
    form.ten_tieng_viet = ' Gập bụng '
    const payload = taoPayloadBaiTap(form, baiTap)
    expect(baiTap.cac_buoc_vi).toEqual(['Một', 'Hai'])
    expect(payload).toMatchObject({
      ten_tieng_viet: 'Gập bụng',
      cac_buoc_vi: ['Bước mới', 'Bước hai'],
      updated_at: baiTap.updated_at,
    })
    for (const truong of ['ma_nguon', 'nguon_du_lieu', 'ten_bai_tap', 'anh_url', 'trang_thai'])
      expect(payload).not.toHaveProperty(truong)
  })

  it('payload thêm chuẩn hóa mã và giữ nội dung text', () => {
    const form = taoBieuMauBaiTap()
    Object.assign(form, {
      ma_nguon: ' a001 ',
      ten_bai_tap: ' Bài mới ',
      huong_dan_vi: '<script>text</script>',
    })
    expect(taoPayloadBaiTap(form)).toMatchObject({
      ma_nguon: 'A001',
      ten_bai_tap: 'Bài mới',
      huong_dan_vi: '<script>text</script>',
      trang_thai: 'HOAT_DONG',
      cac_buoc_vi: [],
    })
    expect(taoPayloadBaiTap(form)).not.toHaveProperty('updated_at')
  })

  it('danh sách bỏ kết quả cũ tới sau và hủy request cũ', async () => {
    const cu = choKetQua()
    const moi = choKetQua()
    baiTapAdminService.taiDanhSach.mockReturnValueOnce(cu.promise).mockReturnValueOnce(moi.promise)
    const trang = { ...DanhSach.data(), $route: { query: {} } }
    const lanCu = DanhSach.methods.taiDanhSach.call(trang)
    const signalCu = baiTapAdminService.taiDanhSach.mock.calls[0][1]
    const lanMoi = DanhSach.methods.taiDanhSach.call(trang)
    expect(signalCu.aborted).toBe(true)
    moi.resolve({ data: [{ id: 2 }], meta: { total: 1 } })
    await lanMoi
    cu.resolve({ data: [{ id: 1 }], meta: { total: 100 } })
    await lanCu
    expect(trang.danhSach).toEqual([{ id: 2 }])
    expect(trang.phanTrang.total).toBe(1)
  })

  it('lưu 409 giữ nội dung đang sửa và chặn gửi lại tới khi tải bản mới', async () => {
    baiTapAdminService.suaBaiTap.mockRejectedValue({
      response: { status: 409, data: { message: 'Bản đã thay đổi.' } },
    })
    const baiTap = { id: 1, nguon_du_lieu: 'admin', updated_at: '2026-10-01 00:00:00.123456' }
    const form = taoBieuMauBaiTap({ ten_bai_tap: 'Nội dung đang sửa' })
    const trang = {
      ...BieuMau.data(),
      baiTap,
      duLieu: form,
      laThem: false,
      $nextTick: async () => {},
      $refs: {},
    }
    await BieuMau.methods.luuBaiTap.call(trang)
    expect(trang.xungDot).toBe(true)
    expect(trang.duLieu.ten_bai_tap).toBe('Nội dung đang sửa')
    await BieuMau.methods.luuBaiTap.call(trang)
    expect(baiTapAdminService.suaBaiTap).toHaveBeenCalledTimes(1)
    expect(trang.dangLuu).toBe(false)
  })

  it('khóa gửi trùng và cập nhật phiên bản sau khi lưu', async () => {
    const cho = choKetQua()
    baiTapAdminService.suaBaiTap.mockReturnValue(cho.promise)
    const trang = {
      ...BieuMau.data(),
      baiTap: { id: 1, nguon_du_lieu: 'admin', updated_at: 'old' },
      laThem: false,
      $nextTick: async () => {},
      $refs: {},
    }
    const lanLuu = BieuMau.methods.luuBaiTap.call(trang)
    await BieuMau.methods.luuBaiTap.call(trang)
    expect(baiTapAdminService.suaBaiTap).toHaveBeenCalledTimes(1)
    cho.resolve({
      data: { id: 1, nguon_du_lieu: 'admin', updated_at: 'new', ten_bai_tap: 'Bài đã lưu' },
      message: 'Đã lưu',
    })
    await lanLuu
    expect(trang.baiTap.updated_at).toBe('new')
    expect(trang.duLieu.ten_bai_tap).toBe('Bài đã lưu')
  })

  it('kết quả lưu không ghi đè biểu mẫu đang tải lại', async () => {
    const cho = choKetQua()
    baiTapAdminService.suaBaiTap.mockReturnValue(cho.promise)
    const trang = {
      ...BieuMau.data(),
      baiTap: { id: 1, nguon_du_lieu: 'admin', updated_at: 'old' },
      laThem: false,
      $nextTick: async () => {},
      $refs: {},
    }
    const lanLuu = BieuMau.methods.luuBaiTap.call(trang)
    trang.lanTai++
    cho.resolve({ data: { id: 1, nguon_du_lieu: 'admin', updated_at: 'stale' }, message: 'Đã lưu' })
    await lanLuu
    expect(trang.baiTap.updated_at).toBe('old')
    expect(trang.thongBao).toBe('')
  })

  it('lỗi validation giữ mã và nội dung để sửa lại', async () => {
    baiTapAdminService.taoBaiTap.mockRejectedValue({
      response: {
        status: 422,
        data: { message: 'Kiểm tra dữ liệu', errors: { ma_nguon: ['Trùng mã'] } },
      },
    })
    const trang = { ...BieuMau.data(), laThem: true, $nextTick: async () => {}, $refs: {} }
    trang.duLieu.ma_nguon = 'A001'
    await BieuMau.methods.luuBaiTap.call(trang)
    expect(trang.duLieu.ma_nguon).toBe('A001')
    expect(trang.loiTruong.ma_nguon).toEqual(['Trùng mã'])
    expect(trang.xungDot).toBe(false)
  })
})
