import { describe, expect, it } from 'vitest'
import HanhTrinh from '../src/views/TongQuan/HanhTrinhKhachHang.vue'
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'

function taoTrang(them = {}) {
  const trang = {
    ...HanhTrinh.data(),
    hanhTrinh: {
      buoi_thang_nay: 0,
      ti_le_hoan_thanh: null,
      so_lich_sap_toi: 0,
      ai: { con_lai: 0, toi_da: 0 },
      buoi_hom_nay: null,
      giao_an: null,
      chi_so: { cac_moc: [] },
      lich_sap_toi: [],
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
    expect(trang.maxBuoi).toBe(4)
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
  it('gộp hai loại cùng ngày và lọc nhất quán tổng, cột, trục; không đổi KPI tự tập', () => {
    const trang = taoTrang({
      buoi_thang_nay: 2,
      tien_do: {
        theo_ngay: [
          { ngay: '2026-10-06', so_buoi: 2, so_buoi_tu_tap: 2, so_buoi_pt: 1, tong_so_buoi: 3 },
          { ngay: '2026-10-07', so_buoi: 0, so_buoi_tu_tap: 0, so_buoi_pt: 2, tong_so_buoi: 2 },
        ],
      },
    })
    expect(trang.tongBuoiChon).toBe(5)
    expect(trang.cacNgayChon[0]).toEqual({ ngay: '2026-10-06', tu_tap: 2, pt: 1, tong: 3 })
    expect(trang.tongTuTap).toBe(2)
    expect(trang.tongPt).toBe(3)
    trang.loaiBuoi = 'PT'
    expect(trang.tongBuoiChon).toBe(3)
    expect(trang.cacNgayChon[0]).toEqual({ ngay: '2026-10-06', tu_tap: 0, pt: 1, tong: 1 })
    expect(trang.maxBuoi).toBe(2)
    expect(trang.thongKe[0].giaTri).toBe('2')
    trang.loaiBuoi = 'TU_TAP'
    expect(trang.tongBuoiChon).toBe(2)
    expect(trang.cacNgayChon[1].tong).toBe(0)
    expect(trang.nhanLoaiBuoi).toBe('Tự tập')
  })
  it('API cũ chỉ có tự tập vẫn hiển thị đúng, bộ lọc PT trả trống và không dựng buổi mẫu', () => {
    const trang = taoTrang({ tien_do: { theo_ngay: [{ ngay: '2026-10-06', so_buoi: 2 }] } })
    expect(trang.tongBuoiChon).toBe(2)
    trang.loaiBuoi = 'PT'
    expect(trang.tongBuoiChon).toBe(0)
    expect(trang.maxBuoi).toBe(2)
  })
  it('render có bộ lọc, hai chuỗi, tooltip và bảng số liệu theo lựa chọn PT', async () => {
    const trang = taoTrang({
      tien_do: {
        so_ngay: 7,
        theo_ngay: [{ ngay: '2026-10-06', so_buoi_tu_tap: 2, so_buoi_pt: 1 }],
      },
    })
    const app = createSSRApp(
      { ...HanhTrinh, data: () => ({ ...HanhTrinh.data(), loaiBuoi: 'PT' }) },
      { hanhTrinh: trang.hanhTrinh },
    )
    app.component('RouterLink', {
      props: ['to'],
      setup:
        (props, { slots }) =>
        () =>
          h('a', { href: props.to }, slots.default?.()),
    })
    const html = await renderToString(app)
    expect(html).toContain('aria-label="Loại buổi tập"')
    expect(html).toContain('Biểu đồ 1 buổi hoàn thành: Với PT')
    expect(html).toContain('06/10/2026: 0 tự tập, 1 với PT; tổng 1 buổi')
    expect(html).toContain('j-chart-bar-pt')
    expect(html).toMatch(/<th scope="col"[^>]*>Với PT<\/th>/)
    expect(html).not.toMatch(/<th scope="col"[^>]*>Tự tập<\/th>/)
    expect(html).toContain('lịch tự tập đã đến tháng này')
    expect(html).toContain('href="/khach-hang/lich-hen"')
  })
})
