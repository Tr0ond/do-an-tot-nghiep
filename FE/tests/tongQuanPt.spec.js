import { describe, expect, it } from 'vitest'
import TongQuanPt from '../src/views/TongQuan/TongQuanPt.vue'

function taoTrang(them = {}) {
  const trang = {
    huanLuyen: {
      hom_nay: '2026-10-07',
      so_buoi_hom_nay: 0,
      so_hoc_vien: 0,
      can_xu_ly: { cho_dat_lich: 0, cho_ket_qua: 0, nhap_pt: 0, hoi_thoai_chua_doc: 0 },
      tuan: {
        da_len_lich: 0,
        hoan_thanh: 0,
        da_huy: 0,
        vang_mat: 0,
        ti_le_hoan_thanh: null,
        theo_ngay: [],
      },
      ...them,
    },
  }
  for (const [ten, ham] of Object.entries(TongQuanPt.methods)) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(TongQuanPt.computed))
    Object.defineProperty(trang, ten, { get: () => ham.call(trang) })
  return trang
}

describe('Dashboard PT', () => {
  it('dữ liệu trống không dùng số liệu mẫu hoặc tỷ lệ hoàn thành giả', () => {
    const trang = taoTrang()
    expect(trang.cacThongKe.map((m) => m.so)).toEqual([0, 0, 0, 0])
    expect(trang.thongKeTuan.at(-1).so).toBe('—')
    expect(trang.maxBuoi).toBe(1)
    expect(trang.tiLe(0, 0)).toBe(0)
    expect(trang.cacViec.map((v) => v.to)).toEqual([
      '/pt/lich-hen',
      '/pt/lich-hen',
      '/pt/hoc-vien',
      '/pt/tin-nhan',
    ])
  })
  it('mở đúng lịch chờ xử lý và lịch hôm nay theo ngày do server trả', () => {
    const trang = taoTrang({
      can_xu_ly: {
        cho_dat_lich: 2,
        cho_ket_qua: 1,
        nhap_pt: 3,
        hoi_thoai_chua_doc: 4,
        giao_an_nhap_id: 53,
        lich_dat: { id: 31 },
        lich_ket_qua: { id: 42 },
      },
    })
    expect(trang.soViec).toBe(10)
    expect(trang.cacViec[0].to).toBe('/pt/lich-hen/31')
    expect(trang.cacViec[1].to).toBe('/pt/lich-hen/42')
    expect(trang.cacViec[2].to).toBe('/pt/ke-hoach/53/sua')
    expect(trang.lichHomNay).toEqual({ path: '/pt/lich-hen', query: { ngay: '2026-10-07' } })
    expect(trang.thaoTacNhanh.find((m) => m.nhan === 'Tạo giáo án').to).toBe('/pt/hoc-vien')
    expect(trang.gio('2026-10-07T03:00:00Z')).toBe('10:00')
  })
  it('đề xuất hết hạn có nhãn riêng, progress không chia cho 0 hoặc vượt 100', () => {
    const trang = taoTrang()
    expect(trang.tenTrangThai('QUA_HAN')).toBe('Đề xuất hết hạn')
    expect(trang.tenTrangThai('QUA_HAN_XAC_NHAN')).toBe('Quá hạn ghi kết quả')
    expect(trang.mauTrangThai('QUA_HAN')).toBe('')
    expect(trang.tiLe(10, 2)).toBe(100)
    expect(trang.tiLe(3, 4)).toBe(75)
    expect(trang.chuCai(' Lan Phương ')).toBe('P')
  })
})
