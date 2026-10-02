import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createSSRApp } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createRouter, createMemoryHistory } from 'vue-router'
import { useXacThucStore } from '../src/stores/xacThuc'
import LichHen from '../src/views/LichHen/index.vue'
import KhungGio from '../src/views/LichHen/KhungGio/index.vue'
import lichHenService from '../src/services/lichHenService'
import { ngayVietNam, tuGioVietNam, khuVucVaiTro } from '../src/utils/lichHen'

vi.mock('../src/services/lichHenService', () => ({
  default: {
    taiLich: vi.fn(),
    taiChiTiet: vi.fn(),
    taiKhung: vi.fn(),
    datLich: vi.fn(),
    taoKhung: vi.fn(),
    doiKhung: vi.fn(),
    thaoTac: vi.fn(),
  },
}))
vi.mock('../src/services/xacThucService', () => ({
  default: { taiTaiKhoan: vi.fn(), dangXuat: vi.fn() },
}))

function taoTrang(component, route = { params: {}, query: {}, fullPath: '/khach-hang/lich-hen' }) {
  const trang = { ...component.data(), $route: route, $router: { push: vi.fn() } }
  for (const [ten, ham] of Object.entries(component.methods)) trang[ten] = ham.bind(trang)
  for (const [ten, ham] of Object.entries(component.computed || {}))
    Object.defineProperty(trang, ten, { get: () => ham.call(trang) })
  return trang
}
function choKetQua() {
  let resolve
  const promise = new Promise((r) => {
    resolve = r
  })
  return { resolve, promise }
}
const lich = {
  id: 8,
  khach_hang: 'Học viên demo',
  pt: 'PT demo',
  bat_dau_luc: '2026-10-03T01:00:00Z',
  ket_thuc_luc: '2026-10-03T02:00:00Z',
  trang_thai: 'CHO_XAC_NHAN',
  han_xac_nhan_dat_lich: '2026-10-02T16:00:00Z',
  dang_ky_goi_tap_id: 3,
  hanh_dong: ['huy'],
}

describe('Lịch huấn luyện M04', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    vi.useFakeTimers()
    setActivePinia(createPinia())
    useXacThucStore().taiKhoan = { id: 1, vai_tro: 'KHACH_HANG' }
  })
  afterEach(() => vi.useRealTimers())
  it('đổi giờ Việt Nam thành UTC, kiểm tra ngày/giờ sai và chỉ nhận ba vai trò', () => {
    expect(tuGioVietNam('2026-10-03', '08:30')).toBe('2026-10-03T01:30:00.000Z')
    expect(ngayVietNam(new Date('2026-10-02T18:30:00Z'))).toBe('2026-10-03')
    for (const [n, g] of [
      ['2026-02-30', '08:00'],
      ['2026-10-03', '24:00'],
      ['bad', '08:00'],
    ])
      expect(tuGioVietNam(n, g)).toBeNull()
    expect(khuVucVaiTro('ADMIN')).toBe('admin')
    expect(khuVucVaiTro('AI')).toBeNull()
  })
  it('đặt lịch double-submit một request, retry lỗi mạng giữ UUID', async () => {
    const trang = taoTrang(KhungGio)
    trang.chonGio({ id: 3 })
    const ma = trang.ma
    const cho = choKetQua()
    lichHenService.datLich
      .mockReturnValueOnce(cho.promise)
      .mockResolvedValueOnce({ data: { id: 8 } })
    const p = trang.datLich()
    await trang.datLich()
    expect(lichHenService.datLich).toHaveBeenCalledTimes(1)
    cho.resolve(Promise.reject(new Error('network')))
    await p
    await trang.datLich()
    expect(lichHenService.datLich.mock.calls[1][0]).toEqual({
      khung_gio_id: 3,
      client_request_id: ma,
    })
    expect(trang.$router.push).toHaveBeenCalledWith('/khach-hang/lich-hen/8')
  })
  it('response ngày cũ không ghi đè khung giờ ngày mới', async () => {
    const trang = taoTrang(KhungGio)
    const cho = choKetQua()
    lichHenService.taiKhung
      .mockReturnValueOnce(cho.promise)
      .mockResolvedValueOnce({ data: [{ id: 2 }], meta: { current_page: 1 } })
    const p = trang.taiDuLieu()
    trang.ngay = '2026-10-04'
    await trang.taiDuLieu()
    cho.resolve({ data: [{ id: 1 }], meta: { current_page: 1 } })
    await p
    expect(trang.ds).toEqual([{ id: 2 }])
  })
  it('chuyển ngày xóa giờ đã chọn và UUID để không đặt nhầm', async () => {
    const trang = taoTrang(KhungGio)
    trang.chonGio({ id: 3 })
    lichHenService.taiKhung.mockResolvedValue({ data: [], meta: { current_page: 1 } })
    trang.doiNgay()
    expect(trang.chon).toBeNull()
    expect(trang.ma).toBeNull()
  })
  it('đăng xuất xóa lịch riêng và bỏ kết quả tới muộn', async () => {
    const trang = taoTrang(LichHen)
    const cho = choKetQua()
    lichHenService.taiLich.mockReturnValueOnce(cho.promise)
    const p = trang.taiDuLieu()
    useXacThucStore().taiKhoan = null
    await trang.taiDuLieu()
    cho.resolve({ data: [lich], meta: {} })
    await p
    expect(trang.ds).toEqual([])
    expect(trang.lich).toBeNull()
  })
  it('không gửi thao tác không được backend cho phép và yêu cầu lý do', async () => {
    const trang = taoTrang(LichHen)
    trang.lich = lich
    trang.chonHanhDong('hoan-thanh')
    expect(trang.hanhDong).toBe('')
    trang.chonHanhDong('huy')
    await trang.luu()
    expect(lichHenService.thaoTac).not.toHaveBeenCalled()
    expect(trang.loi).toContain('lý do')
  })
  it('bộ lọc và phân trang giữ ngày/trạng thái trên URL, gửi đúng khu vực', async () => {
    const trang = taoTrang(LichHen)
    trang.ngay = '2026-10-03'
    trang.trangThai = 'CHO_XAC_NHAN'
    lichHenService.taiLich.mockResolvedValue({ data: [], meta: {} })
    await trang.doiTrang(2)
    expect(trang.$router.push).toHaveBeenCalledWith({
      path: '/khach-hang/lich-hen',
      query: { page: 2, ngay: '2026-10-03', trang_thai: 'CHO_XAC_NHAN' },
    })
    expect(lichHenService.taiLich.mock.calls[0].slice(0, 2)).toEqual([
      'khach-hang',
      { page: 2, ngay: '2026-10-03', trang_thai: 'CHO_XAC_NHAN' },
    ])
  })
  it('xác nhận buổi chỉ gửi một lần và response không lộ sang trang khác', async () => {
    const trang = taoTrang(LichHen)
    trang.lich = { ...lich, hanh_dong: ['huy'] }
    trang.hanhDong = 'huy'
    trang.lyDo = 'Bận công việc'
    const cho = choKetQua()
    lichHenService.thaoTac.mockReturnValueOnce(cho.promise)
    const p = trang.luu()
    await trang.luu()
    expect(lichHenService.thaoTac).toHaveBeenCalledTimes(1)
    trang.$route.fullPath = '/khach-hang/lich-hen/9'
    trang.lich = null
    cho.resolve({ data: { ...lich, trang_thai: 'DA_HUY' } })
    await p
    expect(trang.lich).toBeNull()
  })
})

describe('Render lịch hẹn có dữ liệu', () => {
  async function render(component, path, data, vaiTro = 'KHACH_HANG') {
    const pinia = createPinia()
    useXacThucStore(pinia).taiKhoan = { id: 1, ho_ten: 'Tài khoản demo', vai_tro: vaiTro }
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/:khuVuc/lich-hen/:id', component: { render: () => null } },
        { path: '/:pathMatch(.*)*', component: { render: () => null } },
      ],
    })
    await router.push(path)
    const app = createSSRApp({
      ...component,
      data() {
        return { ...component.data(), ...data }
      },
    })
    app.use(pinia).use(router)
    return renderToString(app)
  }
  it('KH thấy thời gian, trạng thái, lịch và quyền hủy', async () => {
    const html = await render(LichHen, '/khach-hang/lich-hen/8', { lich })
    expect(html).toContain('Chờ PT xác nhận')
    expect(html).toContain('Hủy lịch')
    expect(html).toContain('Học viên demo')
    expect(html).not.toContain('Xác nhận hoàn thành')
  })
  it('PT thấy giờ rảnh đã được giữ và nút mở giờ', async () => {
    const html = await render(
      KhungGio,
      '/pt/khung-gio',
      {
        ds: [
          {
            id: 3,
            bat_dau_luc: lich.bat_dau_luc,
            ket_thuc_luc: lich.ket_thuc_luc,
            trang_thai: 'MO',
            dang_giu: true,
            co_the_doi: false,
          },
        ],
      },
      'HUAN_LUYEN_VIEN',
    )
    expect(html).toContain('Đang có lịch hẹn')
    expect(html).toContain('Mở giờ rảnh')
    expect(html).not.toContain('Đóng giờ')
  })
  it('Admin đóng quá hạn có lý do và không hoàn thành thay PT', async () => {
    const html = await render(
      LichHen,
      '/admin/lich-hen/8',
      {
        lich: { ...lich, trang_thai: 'QUA_HAN_XAC_NHAN', hanh_dong: ['dong-xu-ly'] },
        hanhDong: 'dong-xu-ly',
      },
      'ADMIN',
    )
    expect(html).toContain('Quá hạn xác nhận')
    expect(html).toContain('Lý do')
    expect(html).toContain('Xác nhận thao tác')
    expect(html).not.toContain('Xác nhận hoàn thành')
  })
})
