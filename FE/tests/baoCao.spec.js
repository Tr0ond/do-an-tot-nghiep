import { beforeEach, describe, expect, it, vi } from 'vitest'
import BaoCaoAdmin from '../src/views/TongQuan/BaoCaoAdmin.vue'
import tongQuanService from '../src/services/tongQuanService'
import { ngayVietNam, luiNgay, duongBieuDo } from '../src/utils/baoCao'

vi.mock('../src/services/tongQuanService', () => ({ default: { taiBaoCao: vi.fn() } }))

function taoTrang() {
  const trang = { ...BaoCaoAdmin.data() }
  for (const [ten, ham] of Object.entries(BaoCaoAdmin.methods)) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(BaoCaoAdmin.computed))
    Object.defineProperty(trang, ten, { get: () => ham.call(trang) })
  return trang
}
function choKetQua() {
  let resolve
  const promise = new Promise((tra) => {
    resolve = tra
  })
  return { promise, resolve }
}
function duLieu(so = 0, trang = 1) {
  return {
    bo_loc: {
      tu_ngay: '2026-10-01',
      den_ngay: '2026-10-03',
      nhom: 'ngay',
      mui_gio: 'Asia/Ho_Chi_Minh',
    },
    trong_ky: { thuc_thu: so },
    bieu_do: [
      {
        ky: '2026-10-01',
        thuc_thu: so,
        tien_da_nhan: Math.max(0, so),
        tien_da_hoan: Math.max(0, -so),
      },
    ],
    theo_goi: [],
    pt: { data: [], meta: { current_page: trang, last_page: 2, total: 21 } },
  }
}

describe('M09 báo cáo Admin', () => {
  it('hai đường tiền dùng cùng tỷ lệ, giữ đúng khoản hoàn âm', () => {
    const moc = [
      { thuc_thu: -100, tien_da_nhan: 0 },
      { thuc_thu: 200, tien_da_nhan: 500 },
    ]
    const mien = { duoi: -100, tren: 500 }
    const net = duongBieuDo(moc, 'thuc_thu', mien)
    const nhan = duongBieuDo(moc, 'tien_da_nhan', mien)
    expect(net.zero).toBe(nhan.zero)
    expect(net.diem[0].y).toBe(180)
    expect(nhan.diem[0].y).toBe(net.zero)
    expect(nhan.diem[1].y).toBe(20)
    expect(net.diem[1].y).toBeGreaterThan(nhan.diem[1].y)
  })
  it('chỉ số không suy diễn tăng trưởng và tỷ lệ AI loại yêu cầu đang xử lý', () => {
    const trang = taoTrang()
    trang.duLieu = {
      ...duLieu(-100),
      hien_tai: { goi_dang_su_dung: 2 },
      van_hanh: {
        can_xu_ly: { khach_cho_pt: 1 },
        ai: { yeu_cau: 10, thanh_cong: 6, loi: 2, dang_xu_ly: 2 },
        lich_hom_nay: { tong: 3, trang_thai: { HOAN_THANH: 1 }, data: [] },
      },
    }
    expect(trang.cacChiSo).toHaveLength(6)
    expect(trang.cacChiSo.find((m) => m.ma === 'thuc_thu').so).toBe('-100 ₫')
    expect(trang.cacChiSo.find((m) => m.ma === 'cho_pt').so).toBe('1')
    expect(trang.tyLeAi).toBe('75%')
    trang.duLieu.van_hanh.ai = { thanh_cong: 0, loi: 0 }
    expect(trang.tyLeAi).toBe('—')
    expect(trang.maxAi).toBe(1)
  })
  it('xếp gói theo đơn nhận tiền và giới hạn tỷ lệ cột cho dữ liệu bằng 0', () => {
    const trang = taoTrang()
    const cacGoi = [
      { ten_goi: 'A', so_don_nhan_tien: 1 },
      { ten_goi: 'B', so_don_nhan_tien: 3 },
    ]
    trang.duLieu = { ...duLieu(), theo_goi: cacGoi }
    expect(trang.cacGoi.map((g) => g.ten_goi)).toEqual(['B', 'A'])
    expect(cacGoi[0].ten_goi).toBe('A')
    expect(trang.maxDonGoi).toBe(3)
    trang.duLieu.theo_goi = [{ so_don_nhan_tien: 0 }]
    expect(trang.maxDonGoi).toBe(1)
  })
  it('nút chọn nhanh tải kỳ mới và đặt lại trang PT', async () => {
    const trang = taoTrang()
    tongQuanService.taiBaoCao.mockResolvedValue({ data: duLieu() })
    await trang.chonNhanh('7')
    expect(tongQuanService.taiBaoCao).toHaveBeenCalledWith(
      { tu_ngay: luiNgay(trang.homNay, 6), den_ngay: trang.homNay, nhom: 'ngay', pt_page: 1 },
      trang.boHuy.signal,
    )
  })
  beforeEach(() => {
    vi.resetAllMocks()
  })
  it('đổi ngày tại đúng 0 giờ Việt Nam và lùi qua tháng nhuận', () => {
    expect(ngayVietNam(new Date('2026-10-03T16:59:59Z'))).toBe('2026-10-03')
    expect(ngayVietNam(new Date('2026-10-03T17:00:00Z'))).toBe('2026-10-04')
    expect(luiNgay('2024-03-01', 1)).toBe('2024-02-29')
    expect(luiNgay('2026-01-01', 1)).toBe('2025-12-31')
  })
  it('biểu đồ chứa số âm, số dương và mốc 0 trong vùng vẽ', () => {
    const h = duongBieuDo([{ thuc_thu: -50000 }, { thuc_thu: 0 }, { thuc_thu: 100000 }])
    expect(h.duoi).toBe(-50000)
    expect(h.tren).toBe(100000)
    expect(h.diem[0].y).toBe(180)
    expect(h.diem[2].y).toBe(20)
    expect(h.diem[1].y).toBe(h.zero)
    expect(h.zero).toBeGreaterThan(20)
    expect(h.zero).toBeLessThan(180)
  })
  it('biểu đồ rỗng, toàn 0 hoặc một mốc không sinh NaN/Infinity', () => {
    for (const arr of [[], [{ thuc_thu: 0 }], [{ thuc_thu: -1 }]]) {
      const h = duongBieuDo(arr)
      expect(Number.isFinite(h.zero)).toBe(true)
      expect(h.points).not.toMatch(/NaN|Infinity/)
      if (arr.length) expect(h.diem[0].x).toBe(360)
    }
  })
  it('gửi bộ lọc, tín hiệu hủy và chấp nhận thực thu bằng 0/âm', async () => {
    const trang = taoTrang()
    trang.boLoc = { tu_ngay: '2026-10-01', den_ngay: '2026-10-03', nhom: 'ngay' }
    for (const so of [0, -50000]) {
      tongQuanService.taiBaoCao.mockResolvedValue({ data: duLieu(so) })
      await trang.taiBaoCao()
      expect(tongQuanService.taiBaoCao).toHaveBeenLastCalledWith(
        { ...trang.boLoc, pt_page: 1 },
        trang.boHuy.signal,
      )
      expect(trang.duLieu.trong_ky.thuc_thu).toBe(so)
      expect(trang.coDongTien).toBe(so !== 0)
      expect(trang.dangTai).toBe(false)
      expect(trang.thongBao).toBe('')
    }
  })
  it('phân trang PT giữ kỳ đã xem dù người dùng đang sửa bộ lọc', async () => {
    const trang = taoTrang()
    tongQuanService.taiBaoCao.mockResolvedValue({ data: duLieu() })
    await trang.taiBaoCao()
    trang.boLoc.tu_ngay = '2026-09-01'
    tongQuanService.taiBaoCao.mockResolvedValue({ data: duLieu(0, 2) })
    await trang.taiBaoCao(2, true)
    expect(tongQuanService.taiBaoCao.mock.lastCall[0]).toMatchObject({
      tu_ngay: '2026-10-01',
      pt_page: 2,
    })
    expect(trang.trangPt).toBe(2)
    await trang.taiBaoCao(1)
    expect(tongQuanService.taiBaoCao.mock.lastCall[0]).toMatchObject({
      tu_ngay: '2026-09-01',
      pt_page: 1,
    })
  })
  it('request mới xóa số liệu kỳ cũ và bỏ qua phản hồi đến muộn', async () => {
    const trang = taoTrang()
    trang.duLieu = duLieu(100)
    const cho = choKetQua()
    tongQuanService.taiBaoCao
      .mockReturnValueOnce(cho.promise)
      .mockResolvedValueOnce({ data: duLieu(200) })
    const cu = trang.taiBaoCao()
    const signal = trang.boHuy.signal
    expect(trang.duLieu).toBeNull()
    expect(trang.dangTai).toBe(true)
    await trang.taiBaoCao()
    expect(signal.aborted).toBe(true)
    cho.resolve({ data: duLieu(300) })
    await cu
    expect(trang.duLieu.trong_ky.thuc_thu).toBe(200)
  })
  it('rời trang hủy request và không đưa kết quả trở lại', async () => {
    const trang = taoTrang()
    const cho = choKetQua()
    tongQuanService.taiBaoCao.mockReturnValue(cho.promise)
    const pending = trang.taiBaoCao()
    BaoCaoAdmin.beforeUnmount.call(trang)
    expect(trang.boHuy.signal.aborted).toBe(true)
    cho.resolve({ data: duLieu(100) })
    await pending
    expect(trang.duLieu).toBeNull()
  })
  it('tải trang PT giữ bảng kỳ cũ trong lúc chờ nhưng xóa khi bị từ chối quyền', async () => {
    const trang = taoTrang()
    trang.duLieu = duLieu(100)
    trang.boLocDaTai = trang.duLieu.bo_loc
    const cho = choKetQua()
    tongQuanService.taiBaoCao.mockReturnValue(cho.promise)
    const pending = trang.taiBaoCao(2, true)
    expect(trang.duLieu.trong_ky.thuc_thu).toBe(100)
    expect(trang.dangTai).toBe(true)
    cho.resolve({ data: duLieu(100, 2) })
    await pending
    tongQuanService.taiBaoCao.mockRejectedValue({ response: { status: 403 } })
    await trang.taiBaoCao(1, true)
    expect(trang.duLieu).toBeNull()
    expect(trang.hetPhien).toBe(true)
  })
  it('validation, hết quyền và lỗi mạng không giữ số tiền cũ; có thể thử lại', async () => {
    const trang = taoTrang()
    for (const status of [422, 403, 401, 419]) {
      trang.duLieu = duLieu(100)
      tongQuanService.taiBaoCao.mockRejectedValue({
        response: {
          status,
          data: {
            message: 'Lỗi thử',
            errors: status === 422 ? { den_ngay: ['Tối đa 366 ngày.'] } : {},
          },
        },
      })
      await trang.taiBaoCao()
      expect(trang.duLieu).toBeNull()
      expect(trang.hetPhien).toBe(status !== 422)
      expect(trang.thongBao).toBe(status === 422 ? 'Tối đa 366 ngày.' : 'Lỗi thử')
    }
    tongQuanService.taiBaoCao.mockRejectedValue(new Error('Mạng'))
    await trang.taiBaoCao()
    expect(trang.hetPhien).toBe(false)
    expect(trang.thongBao).toContain('Không thể kết nối')
    tongQuanService.taiBaoCao.mockResolvedValue({ data: duLieu() })
    await trang.taiBaoCao()
    expect(trang.thongBao).toBe('')
    expect(trang.duLieu).not.toBeNull()
  })
})
