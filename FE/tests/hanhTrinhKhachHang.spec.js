import { describe, expect, it } from 'vitest'
import HanhTrinh from '../src/views/TongQuan/HanhTrinhKhachHang.vue'

function taoTrang(them = {}) {
  const trang = {
    hanhTrinh: {
      buoi_thang_nay: 0,
      ti_le_hoan_thanh: null,
      so_lich_sap_toi: 0,
      ai: { con_lai: 0, toi_da: 0 },
      buoi_hom_nay: null,
      giao_an: null,
      chi_so: { cac_moc: [] },
      tien_do: { theo_ngay: [] },
      ...them,
    },
  }
  for (const [ten, ham] of Object.entries(HanhTrinh.methods)) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(HanhTrinh.computed))
    Object.defineProperty(trang, ten, { get: () => ham.call(trang) })
  return trang
}

describe('Hành trình KH', () => {
  it('dữ liệu trống giữ dấu chưa có số đo/tỷ lệ, không sinh thống kê mẫu', () => {
    const trang = taoTrang()
    expect(trang.thongKe.map((x) => x.giaTri)).toEqual(['0', '—', '0', '0/0'])
    expect(trang.duongBuoi).toBe('/khach-hang/lich-tap')
    expect(trang.diemBmi.diem).toEqual([])
    expect(trang.maxBuoi).toBe(2)
    expect(trang.chenhLech(null)).toBe('—')
    expect(trang.so(undefined)).toBe('—')
  })
  it('chỉ mở đúng nhật ký từ lịch của API, hiển thị số tổng và quota thật', () => {
    const trang = taoTrang({
      buoi_thang_nay: 2,
      ti_le_hoan_thanh: 50,
      so_lich_sap_toi: 3,
      ai: { con_lai: 7, toi_da: 10 },
      buoi_hom_nay: { id: 41, trang_thai: 'DANG_TAP' },
      tien_do: { theo_ngay: [{ so_buoi: 3 }, { so_buoi: 0 }] },
    })
    expect(trang.duongBuoi).toBe('/khach-hang/lich-tap/41')
    expect(trang.thongKe.map((x) => x.giaTri)).toEqual(['2', '50%', '3', '7/10'])
    expect(trang.maxBuoi).toBe(3)
    expect(trang.trangThai('CHO_XAC_NHAN')).toBe('PT · Chờ xác nhận')
    expect(trang.gio('2026-10-05T11:00:00Z')).toBe('18:00')
  })
  it('BMI giữ khoảng ngày đo thật và không thêm mốc khi thiếu chiều cao', () => {
    const trang = taoTrang({
      chi_so: {
        cac_moc: [
          { ngay_ghi: '2026-10-01', bmi: 22.86 },
          { ngay_ghi: '2026-10-02', bmi: null },
          { ngay_ghi: '2026-10-05', bmi: 22.53 },
        ],
      },
    })
    expect(trang.diemBmi.diem).toHaveLength(2)
    expect(trang.diemBmi.diem.map((d) => d.x)).toEqual([105, 610])
    expect(trang.diemBmi.duong).not.toContain('NaN')
    expect(trang.chenhLech(-0.33)).toBe('-0,33')
    expect(trang.chenhLech(0.3)).toBe('+0,3')
  })
})
